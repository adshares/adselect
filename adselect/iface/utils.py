from collections import defaultdict

from twisted.internet import defer

from adselect.db import utils as db_utils
from adselect.stats import utils as stats_utils

FILTER_SEPARATOR = '--'


@defer.inlineCallbacks
def create_or_update_campaign(cmpobj):
    """
    Create or update (if existing) campaign data, asynchronously. The data can contain banners.

    1. Add campaign data.
    2. Remove old banners for this campaign.
    3. Create or update banner data, if included with the campaign data.

    :param cmpobj: Campaign document.
    :return: Deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    # Save changes only to database
    campaign_doc = cmpobj.to_json()
    del campaign_doc['banners']
    yield db_utils.update_campaign(campaign_doc)

    # Delete previous banners
    yield db_utils.delete_campaign_banners(cmpobj.campaign_id)

    for banner in cmpobj.banners:
        banner_doc = banner.to_json()
        banner_doc['campaign_id'] = cmpobj.campaign_id
        yield db_utils.update_banner(banner_doc)
        # stats_cache.BANNERS[banner.banner_size].append(banner.banner_id)


@defer.inlineCallbacks
def delete_campaign(campaign_id):
    """
    Remove campaign and banners for that campaign.

    :param campaign_id: Identifier of the campaign.
    :return: Deferred.
    """
    # Save changes only to database
    yield db_utils.delete_campaign(campaign_id)
    yield db_utils.delete_campaign_banners(campaign_id)


def add_impression(imobj):
    """
    Record the impression, by passing it to the Statistics module.

    :param imobj: Impression document.
    :return:
    """
    # Change counter only  in stats cache
    stats_utils.process_impression(imobj.banner_id,
                                   imobj.publisher_id,
                                   imobj.keywords,
                                   imobj.paid_amount)


@defer.inlineCallbacks
def validate_banner_with_banner_request(banner_request, proposed_banner_id):
    """
    Make sure the banner is ok for this request.

    1. Does the banner exist?
    2. Does the campaign for this banner exist?
    3. Is the campaign active?
    4. Are banner keywords ok for this campaign?

    :param banner_request:
    :param proposed_banner_id:
    :return:
    """
    # Check if they actually exist (active)

    banner_doc = yield db_utils.get_banner(proposed_banner_id)
    if not banner_doc:
        defer.returnValue(False)

    campaign_id = banner_doc['campaign_id']
    campaign_doc = yield db_utils.get_campaign(campaign_id)

    # Check if campaign is active.
    if not campaign_doc:
        defer.returnValue(False)

    if not stats_utils.is_campaign_active(campaign_doc):
        defer.returnValue(False)

    # Validate campaign filters, Validate impression filters
    if not validate_keywords(campaign_doc['filters'], banner_request.keywords) or \
       not validate_keywords(banner_request.banner_filters.to_json(), campaign_doc['keywords']):
        defer.returnValue(False)

    defer.returnValue(True)


@defer.inlineCallbacks
def select_banner(banners_requests):
    """
    Select_banner function should work as follow:

    1. Select banners which are paid a lot.
    2. Some percent of selected banners should be new banners without payments stats
    3. The same user shouldn't take the same banners every time.

    :param banners_requests: Iterable of banner documents.
    :return:
    """

    responses_dict = defaultdict()
    for banner_request in banners_requests:

        proposed_banners = stats_utils.select_best_banners(banner_request.publisher_id,
                                                           banner_request.banner_size,
                                                           banner_request.keywords)

        # Validate banners
        for banner_id in proposed_banners:

            banner_ok = yield validate_banner_with_banner_request(banner_request, banner_id)
            if banner_ok:
                responses_dict[banner_request.request_id] = banner_id
                break

    defer.returnValue(responses_dict)


def validate_keywords(filters_dict, keywords):
    """
    Validate required and excluded keywords.

    :param filters_dict: Required and excluded keywords
    :param keywords: Keywords being tested.
    :return: True or False
    """
    return validate_require_keywords(filters_dict, keywords) and validate_exclude_keywords(filters_dict, keywords)


def validate_require_keywords(filters_dict, keywords):
    """
    Validate required and excluded keywords.

    :param filters_dict: Required and excluded keywords
    :param keywords: Keywords being tested.
    :return: True or False
    """
    for category_keyword in filters_dict.get('require'):
        if category_keyword not in keywords:
            return False

        for category_keyword_value in category_keyword:
            keyword_value = keywords.get(category_keyword)

            bounds = category_keyword_value.split(FILTER_SEPARATOR)
            if (len(bounds) == 2 and bounds[0] < keyword_value < bounds[1]) \
               or (bounds[0] == keyword_value):
                    break
        else:
            return False

    return True


def validate_exclude_keywords(filters_dict, keywords):
    """
    Validate required and excluded keywords.

    :param filters_dict: Required and excluded keywords
    :param keywords: Keywords being tested.
    :return: True or False
    """
    for category_keyword in filters_dict.get('exclude'):
        if category_keyword not in keywords:
            continue

        for category_keyword_value in category_keyword:
            keyword_value = keywords.get(category_keyword)

            bounds = category_keyword_value.split(FILTER_SEPARATOR)
            if (len(bounds) == 2 and bounds[0] < keyword_value < bounds[1]) \
               or (bounds[0] == keyword_value):
                    return False

    return True

from twisted.internet import defer

from adselect.contrib import filters
from adselect.db import utils as db_utils
from adselect.iface import protocol as iface_proto
from adselect.stats import utils as stats_utils


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
    stats_utils.update_impression(imobj.banner_id,
                                  imobj.publisher_id,
                                  imobj.keywords,
                                  imobj.paid_amount)


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

    responses_dict = {}
    for banner_request in banners_requests:
        responses_dict[banner_request.request_id] = None

    for banner_request in banners_requests:
        proposed_banners = stats_utils.select_best_banners(banner_request.publisher_id,
                                                           banner_request.banner_size,
                                                           banner_request.keywords)

        for banner_id in proposed_banners:
            banner_doc = yield db_utils.get_banner(banner_id)
            if not banner_doc:
                continue

            campaign_id = banner_doc['campaign_id']
            campaign_doc = yield db_utils.get_campaign(campaign_id)
            if not campaign_doc:
                continue

            if not stats_utils.is_campaign_active(campaign_doc):
                continue

            # Validate campaign filters
            if not validate_keywords(campaign_doc['filters'], banner_request.keywords):
                continue

            # Validate impression filters
            if not validate_keywords(banner_request.banner_filters.to_json(), campaign_doc['keywords']):
                continue

            responses_dict[banner_request.request_id] = banner_id
            break

    responses = [iface_proto.SelectBannerResponse(request_id=request_id, banner_id=responses_dict[request_id])
                 for request_id in responses_dict]
    defer.returnValue(responses)


def validate_keywords(filters_dict, keywords):
    """
    Validate required and excluded keywords.

    :param filters_dict: Required and excluded keywords
    :param keywords: Keywords being tested.
    :return: True or False
    """

    for filter_json in filters_dict.get('require'):
        keyword = filter_json['keyword']
        if keyword not in keywords:
            return False

        filter_obj = filters.json2filter(filter_json['filter'])
        if not filter_obj.is_valid(keywords.get(keyword)):
            return False

    for filter_json in filters_dict.get('exclude'):
        keyword = filter_json['keyword']
        if keyword not in keywords:
            continue

        filter_obj = filters.json2filter(filter_json['filter'])
        if filter_obj.is_valid(keywords.get(keyword)):
            return False

    return True

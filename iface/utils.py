from twisted.internet import defer

from adselect.contrib import filters
from adselect.db import utils as db_utils
from adselect.iface import protocol as iface_proto
from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils


@defer.inlineCallbacks
def create_or_update_campaign(cmpobj):
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
    # Save changes only to database
    yield db_utils.delete_campaign(campaign_id)
    yield db_utils.delete_campaign_banners(campaign_id)


def add_impression(imobj):
    # Change counter only  in stats cache
    stats_cache.update_impression(imobj.banner_id,
                                  imobj.publisher_id,
                                  imobj.keywords,
                                  imobj.paid_amount)


@defer.inlineCallbacks
def select_banner(banners_requests):
    """
        select_banner function should work as follow:
        1) Select banners which are paid a lot.
        2) Some percent of selected banners should be new banners without payments stats
        3) The same user shoudn't take the same banners every time.

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
            if not validate_filters(campaign_doc['filters'], banner_request.keywords):
                continue

            # Validate impression filters
            if not validate_filters(banner_request.banner_filters.to_json(), campaign_doc['keywords']):
                continue

            responses_dict[banner_request.request_id] = banner_id
            break

    responses = [iface_proto.SelectBannerResponse(request_id=request_id, banner_id=responses_dict[request_id])
                 for request_id in responses_dict]
    defer.returnValue(responses)


def validate_filters(filters_dict, keywords):
    for filter_json in filters_dict.get('require'):
        keyword = filter_json['keyword']
        if not keyword in keywords:
            return False

        filter_obj = filters.json2filter(filter_json['filter'])
        if not filter_obj.is_valid(keywords.get(keyword)):
            return False

    for filter_json in filters_dict.get('exclude'):
        keyword = filter_json['keyword']
        if not keyword in keywords:
            continue

        filter_obj = filters.json2filter(filter_json['filter'])
        if filter_obj.is_valid(keywords.get(keyword)):
            return False

    return True


if __name__ == "__main__":
    keywords = {u'context_lorem': 1, u'locale': u'en_us', u'banner_size': u'160x600', u'context_lipsum': 1, u'device_type': u'desktop', u'zone': u'website.priv/2', u'tid': u'7e_dy_8i1u3gofhjxq9jgzdahwfj7g', u'inframe': u'no', u'screen_width': 1920, u'context_what': 1, u'host': u'website.priv', u'path': u'website.priv/', u'context_facts': 1, u'context_ipsum': 1, u'context_generator': 1, u'context_lorem ipsum': 1, u'context_text': 1, u'browser_name': u'firefox', u'context_information': 1, u'platform_name': u'macosx', u'context_generate': 1, u'screen_height': 1080}
    filters_dict = {u'exclude': [], u'require': [{u'filter': {u'args': u'mac', u'type': u'='}, u'keyword': u'platform_name'}]}
    print validate_filters(filters_dict, keywords)




from adselect.iface import protocol as iface_proto
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils

from twisted.internet import defer

@defer.inlineCallbacks
def create_or_update_campaign(cmpobj):
    # Delete campaign if exists
    delete_campaign(cmpobj.campaign_id)

    # Save changes only to database
    campaign_doc = cmpobj.to_json()
    del campaign_doc['banners']
    yield db_utils.add_or_update_campaign(campaign_doc)

    for banner in cmpobj.banners:
        banner_doc = banner.to_json()
        banner_doc['campaign_id'] = cmpobj.campaign_id
        yield db_utils.add_or_update_banner(banner_doc)


def delete_campaign(cmpid_list):
    # Save changes only to database
    db_utils.delete_campaigns(cmpid_list)


def add_impression(imobj):
    # Change counter only  in stats cache
    stats_cache.update_impression(imobj.banner_id,
                                  imobj.publisher_id,
                                  imobj.keywords,
                                  imobj.paid_amount)


def select_banner(banners_requests):
    """
        select_banner function should work as follow:
        1) Select banners which are paid a lot.
        2) Some percent of selected banners should be new banners without payments stats
        3) The same user shoudn't take the same banners every time.

    """

    response = []
    for banner_request in banners_requests:
        proposed_banners = stats_cache.select_best_banners(banner_request.publisher_id,
                                                           banner_request.banner_size,
                                                           banner_request.keywords)

        #TODO: add banners filtering

        response.append(
            iface_proto.SelectBannerResponse(
                request_id = banner_request.request_id,
                banner_id = proposed_banners[0] if proposed_banners else None
            )
        )

    return response





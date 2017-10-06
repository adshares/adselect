from adselect.iface import protocol as iface_proto
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils

from twisted.internet import defer

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
    yield db_utils.delete_campaigns(campaign_id)
    yield db_utils.delete_campaign_banners(campaign_id)


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





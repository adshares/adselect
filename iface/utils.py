from adselect.iface import protocol as iface_proto
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils

from twisted.internet import defer

def create_or_update_campaign(cmpobj):
    # Delete campaign if exists
    delete_campaign(cmpobj.campaign_id)

    # Save changes only to database
    campaign_doc = cmpobj.to_json()
    del campaign_doc['banners']

    banners_doc_list = []
    for banner in cmpobj.banners:
        banner_doc = banner.to_json()
        banner_doc['campaign_id'] = cmpobj.campaign_id
        banners_doc_list.append(banner_doc)

    db_utils.add_or_update_campaign(campaign_doc, banners_doc_list)


def delete_campaign(cmpid_list):
    # Save changes only to database
    db_utils.delete_campaigns(cmpid_list)


def add_impression(imobj):
    def wraper(banner_doc):
        # Change counter only  in stats cache
        stats_cache.update_impression(imobj.banner_id,
                                      banner_doc['banner_size'],
                                      imobj.publisher_id,
                                      imobj.keywords,
                                      imobj.paid_amount)

    db_utils.get_banner({'banner_id': imobj.banner_id}, wraper)


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





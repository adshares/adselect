from adselect.iface import protocol as iface_proto
from adselect.stats import utils as stats_utils
from adselect.db import utils as db_utils


def select_banner(banners_requests):
    proposed_banners = stats_utils.select_banner([
        (obj.request_id, obj.publisher_id, obj.user_id, obj.banner_size, obj.keywords) for obj in banners_requests
    ])

    responses = []
    for request_id, banner_id in proposed_banners:
        responses.append(iface_proto.SelectBannerResponse(
                request_id = request_id,
                banner_id = banner_id
            ))

    return responses


def add_impression(imobj):
    """
        imobj - impression object
    """

    banner = db_utils.get_banner(imobj.banner_id)
    stats_utils.update_impression(imobj.banner_id,
                                  banner.banner_size,
                                  imobj.publisher_id,
                                  imobj.keywords,
                                  imobj.paid_amount)


def create_or_update_campaign(cmpobj):
    # Save changes to database
    campaign_doc = cmpobj.to_json()
    del campaign_doc['banners']

    banners_doc_list = []
    for banner in cmpobj.banners:
        banner_doc = banner.to_json()
        banner_doc['campaign_id'] = cmpobj.campaign_id
        banners_doc_list.append(banner_doc)

    db_utils.add_or_update_campaigns(campaign_doc, banners_doc_list)

    # Update cache with new banners
    # for banner in cmpobj.banners:
    #    stats_utils.add_new_banner(banner.banner_id, banner.banner_size)


def delete_campaign(cmpid_list):
    # Save changes to database
    db_utils.delete_campaigns(cmpid_list)
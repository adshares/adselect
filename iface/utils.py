from adselect.iface import protocol as iface_proto
from adselect.stats import utils as stats_utils


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


def create_or_update_campaign(cmpobj):
    """
        cmpobj - campaign object
    """

    #TODO: add save to banner
    for banner in cmpobj.banners:
        stats_utils.add_new_banner(banner.banner_id, banner.banner_size)


def add_impression(imobj):
    """
        imobj - impression object
    """

    #TODO: get banner size
    stats_utils.update_impression(imobj.banner_id,
                                  "banner_size",
                                  imobj.publisher_id,
                                  imobj.keywords,
                                  imobj.paid_amount)


def delete_campaign(cmpid):
    """
        cmpid - camapign id
    """

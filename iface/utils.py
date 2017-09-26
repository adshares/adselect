from adselect.selector import stats as selector_stats
from adselect.iface import models as iface_models


def select_banner(banners_requests):
    """
        select_banner function should work as follow:
        1) Select banners which are paid a lot.
        2) Some percent of selected banners should be new banners without payments stats
        3) The same user shoudn't take the same banners every time.

    """

    requests = []
    for banner_request in banners_requests:
        proposed_banners = selector_stats.select_best_banners(banner_request.publisher_id,
                                                              banner_request.banner_size,
                                                              banner_request.keywords)

        #TODO: add banners filtering

        requests.append(
            iface_models.SelectBannerResponse(
                request_id = banner_request.request_id,
                banner_id = proposed_banners[0] if proposed_banners else None
            )
        )

    return requests


def create_or_update_campaign(cmpobj):
    """
        cmpobj - campaign object
    """

    for banner in cmpobj.banners:
        selector_stats.add_new_banner(banner.banner_id, banner.banner_size)


def add_impression(imobj):
    """
        imobj - impression object
    """

    #TODO: get banner size
    selector_stats.update_impression(imobj.banner_id,
                                     "banner_size",
                                     imobj.publisher_id,
                                     imobj.keywords,
                                     imobj.paid_amount)


def delete_campaign(cmpid):
    """
        cmpid - camapign id
    """



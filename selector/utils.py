"""
    select_banner function should work as follow:
    1) Select banners which are paid a lot.
    2) Some percent of selected banners should be new banners without payments stats
    3) The same user shoudn't take the same banners every time.

"""

from adselect.selector import stats as selector_stats
from adselect.iface import models as iface_models


def select_banner(select_banner_request_list):
    request = []

    for banner_request in select_banner_request_list:
        request.append(
            iface_models.SelectBannerResponse(
                request_id = banner_request.request_id,
                banner_id = 'a3878c8608ed42afa18dd6edcbf3948e'
            )
        )

    return request


def update_stats():
    pass
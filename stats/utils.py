from adselect.stats import cache as stats_cache

def select_banner(banners_list):
    """
        select_banner function should work as follow:
        1) Select banners which are paid a lot.
        2) Some percent of selected banners should be new banners without payments stats
        3) The same user shoudn't take the same banners every time.

    """

    selected_banners = []
    for request_id, publisher_id, user_id, banner_size, impression_keywords in banners_list:
        proposed_banners = stats_cache.select_best_banners(publisher_id,
                                                           banner_size,
                                                           impression_keywords)

        #TODO: add banners filtering

        selected_banners.append(
            (request_id, proposed_banners[0] if proposed_banners else None)
        )

    return selected_banners


def update_impression(banner_id, banner_size, publisher_id, impression_keywords, paid_amount):
    return stats_cache.update_impression(banner_id, banner_size, publisher_id, impression_keywords, paid_amount)


def add_new_banner(banner_id, banner_size):
    return stats_cache.add_new_banner(banner_id, banner_size)




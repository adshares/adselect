from twisted.internet import defer, reactor

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils


def save_or_load_banners_impressions_count():
    # Load or save current BANNERS_IMPRESSIONS_COUNT
    pass


def save_or_load_keyword_impression_paid_amount():
    # Load or save current KEYWORD_IMPRESSION_PAID_AMOUNT
    pass


def load_new_banners():
    print "Updating new banners"
    NEW_BANNERS = {}

    def handle_wrapper(banner_doc):
        banner_size, banner_id = banner_doc['banner_size'], banner_doc['banner_id']
        if not banner_size in NEW_BANNERS:
            NEW_BANNERS[banner_size]=[]
        NEW_BANNERS[banner_size].append(banner_id)

    def update_new_banners(*args):
        stats_cache.update_new_banners(NEW_BANNERS)

    db_utils.get_banners_iter(handle_wrapper).addCallback(update_new_banners)


def recalculate_stats():

    # Taking from database BANNERS_IMPRESSIONS_COUNT
    save_or_load_banners_impressions_count()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT
    save_or_load_keyword_impression_paid_amount()

    # Creating new banners list
    load_new_banners()

    # Recalculate KEYWORDS_BANNERS

    # Recalcuate BEST_KEYWORDS

def recalculate_stats_task():
    recalculate_stats()
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


def configure_tasks():
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


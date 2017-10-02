from twisted.internet import defer, reactor

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils


def save_banners_impression_count():
    # Save BANNERS_IMPRESSIONS_COUNT to database
    for banner_id, counts_per_publisher_dict in stats_cache.BANNERS_IMPRESSIONS_COUNT.iteritems():
        db_utils.update_banner_impression_count(banner_id, counts_per_publisher_dict)


def load_banners_impression_count():
    # Load BANNERS_IMPRESSIONS_COUNT from database
    def handle_record(record):
        stats_cache.update_banners_impressions_count(record['banner_id'], record['stats'])
    db_utils.get_banner_impression_count_iter(handle_record)


def update_banners_impressions_count():
    if stats_cache.BANNERS_IMPRESSIONS_COUNT is None:
        load_banners_impression_count()
    else:
        save_banners_impression_count()


def save_keyword_impression_paid_amount():
    #Save stats for KEYWORD_IMPRESSION_PAID_AMOUNT
    for banner_id, payment_stats_dict in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.iteritems():
        db_utils.update_banner_payment(banner_id, payment_stats_dict)


def load_keyword_impression_paid_amount():
    #Load stats for KEYWORD_IMPRESSION_PAID_AMOUNT
    def handle_record(record):
        stats_cache.update_keyowrd_impression_paid_amount(record['banner_id'], record['stats'])
    db_utils.get_banner_payment_iter(handle_record)


def update_keyword_impression_paid_amount():
    if stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT is None:
        load_keyword_impression_paid_amount()
    else:
        save_keyword_impression_paid_amount()


def load_new_banners():
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
    update_banners_impressions_count()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT
    update_keyword_impression_paid_amount()

    # Creating new banners list
    load_new_banners()

    # Recalculate KEYWORDS_BANNERS

    # Recalcuate BEST_KEYWORDS

    print "NEW_BANNERS", stats_cache.NEW_BANNERS
    print "KEYWORD_IMPRESSION_PAID_AMOUNT", stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT
    print "BANNERS_IMPRESSIONS_COUNT", stats_cache.BANNERS_IMPRESSIONS_COUNT


def recalculate_stats_task():
    recalculate_stats()
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


def configure_tasks():
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


from twisted.internet import defer, reactor

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils
from adselect import db


def save_banners_impression_count():
    # Save BANNERS_IMPRESSIONS_COUNT to database
    for banner_id, counts_per_publisher_dict in stats_cache.BANNERS_IMPRESSIONS_COUNT.iteritems():
        db_utils.update_banner_impression_count(banner_id, counts_per_publisher_dict)


def load_banners_impression_count():
    # Load BANNERS_IMPRESSIONS_COUNT from database
    def handle_record(record):
        stats_cache.update_banners_impressions_count(record['banner_id'], record['stats'])

    def initlialize_banner_impression_count(*args):
        stats_cache.initialize_banners_impressions_count()

    db_utils.get_banner_impression_count_iter(handle_record).addCallback(initlialize_banner_impression_count)


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
        stats_cache.update_keyword_impression_paid_amount(record['banner_id'], record['stats'])

    def initialize_keyword_impression_paid(*args):
        stats_cache.initialize_keyword_impression_paid_amount()

    db_utils.get_banner_payment_iter(handle_record).addCallback(initialize_keyword_impression_paid)


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

@defer.inlineCallbacks
def recalculate_best_keywords():
    #Update KEYWORDS_BANNERS and BEST_KEYWORDS
    if stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT is None:
        return

    if stats_cache.BANNERS_IMPRESSIONS_COUNT is None:
        return

    KEYWORDS_BANNERS = {}
    for banner_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT:
        banner = yield db.get_banner_collection().find_one({'banner_id': banner_id})

        if not banner:
            print "Warning! Banner %s not in database" %banner_id
            continue

        banner_size = banner['banner_size']

        for publisher_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]:

            keyword_impression_count = stats_cache.BANNERS_IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id)
            if not keyword_impression_count > 0:
                continue

            if publisher_id not in KEYWORDS_BANNERS:
                KEYWORDS_BANNERS[publisher_id] = {}

            if banner['banner_size'] not in KEYWORDS_BANNERS[publisher_id]:
                KEYWORDS_BANNERS[publisher_id][banner_size] = {}

            for keyword, keyword_payment in \
                    stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id].iteritems():


                if keyword not in KEYWORDS_BANNERS[publisher_id][banner_size]:
                    KEYWORDS_BANNERS[publisher_id][banner_size][keyword] = []

                score = 1.0*keyword_payment/keyword_impression_count
                KEYWORDS_BANNERS[publisher_id][banner_size][keyword].append((score, banner_id))

    for publisher_id in KEYWORDS_BANNERS:
        for banner_size in KEYWORDS_BANNERS[publisher_id]:
            for keyword in KEYWORDS_BANNERS[publisher_id][banner_size]:
                KEYWORDS_BANNERS[publisher_id][banner_size][keyword] = \
                    sorted(KEYWORDS_BANNERS[publisher_id][banner_size][keyword], reverse=True)
    stats_cache.update_keywords_banners(KEYWORDS_BANNERS)

    BEST_KEYWORDS = {}
    for publisher_id in KEYWORDS_BANNERS:
        BEST_KEYWORDS[publisher_id] = {}

        for size in KEYWORDS_BANNERS[publisher_id]:
            BEST_KEYWORDS[publisher_id][size] = []

            for keyword, banners_list in KEYWORDS_BANNERS[publisher_id][size].iteritems():
                if not banners_list:
                    continue

                BEST_KEYWORDS[publisher_id][size].append((banners_list[0][0], keyword))

            BEST_KEYWORDS[publisher_id][size] = sorted(BEST_KEYWORDS[publisher_id][size], reverse=True)
            BEST_KEYWORDS[publisher_id][size] = [elem[1] for elem in BEST_KEYWORDS[publisher_id][size]]
    stats_cache.update_best_keywords(BEST_KEYWORDS)


def recalculate_stats():

    # Taking from database BANNERS_IMPRESSIONS_COUNT
    update_banners_impressions_count()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT
    update_keyword_impression_paid_amount()

    # Creating new banners list
    load_new_banners()

    # Recalculate KEYWORDS_BANNERS and BEST_KEYWORDS
    recalculate_best_keywords()


def recalculate_stats_task():
    recalculate_stats()
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


def configure_tasks():
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


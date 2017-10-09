from twisted.internet import defer, reactor

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
from adselect.db import utils as db_utils

import time

@defer.inlineCallbacks
def save_banners_impression_count():
    # Save BANNERS_IMPRESSIONS_COUNT to database
    for banner_id, counts_per_publisher_dict in stats_cache.BANNERS_IMPRESSIONS_COUNT.iteritems():
        banner_stats = yield db_utils.get_banner_impression_count(banner_id)

        #Update db_stats with cache stats
        db_banner_stats = banner_stats['stats'] if banner_stats else {}
        for publisher_id, publisher_impression_count in counts_per_publisher_dict.items():
            if publisher_id not in db_banner_stats:
                db_banner_stats[publisher_id] = 0

            db_banner_stats[publisher_id] += publisher_impression_count

        yield db_utils.update_banner_impression_count(banner_id, db_banner_stats)
        stats_cache.update_banners_impressions_count(banner_id, {})


@defer.inlineCallbacks
def load_banners_impression_count():
    # Load BANNERS_IMPRESSIONS_COUNT from database

    docs, dfr = yield db_utils.get_banner_impression_count_iter()
    while docs:
        for record in docs:
            stats_cache.update_banners_impressions_count(record['banner_id'], record['stats'])
        docs, dfr = yield dfr

    stats_cache.initialize_banners_impressions_count()


def update_banners_impressions_count():
    if stats_cache.BANNERS_IMPRESSIONS_COUNT is None:
        load_banners_impression_count()
    else:
        save_banners_impression_count()


@defer.inlineCallbacks
def save_keyword_impression_paid_amount():
    #Save stats for KEYWORD_IMPRESSION_PAID_AMOUNT
    for banner_id, payment_stats_dict in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.iteritems():
        banner_stats = yield db_utils.get_banner_payment(banner_id)

        db_banner_stats = banner_stats['stats'] if banner_stats else {}
        for publisher_id, publisher_keywords_payment in payment_stats_dict.items():
            if publisher_id not in db_banner_stats:
                db_banner_stats[publisher_id] = {}

            for keyword, payment_amount in publisher_keywords_payment.items():
                if keyword not in db_banner_stats[publisher_id]:
                    db_banner_stats[publisher_id][keyword] = 0

                db_banner_stats[publisher_id][keyword]+=payment_stats_dict[publisher_id][keyword]

        yield db_utils.update_banner_payment(banner_id, db_banner_stats)
        stats_cache.update_keyword_impression_paid_amount(banner_id, {})


@defer.inlineCallbacks
def load_keyword_impression_paid_amount():
    #Load stats for KEYWORD_IMPRESSION_PAID_AMOUNT
    docs, dfr = yield db_utils.get_banner_payment_iter()
    while docs:
        for record in docs:
            stats_cache.update_keyword_impression_paid_amount(record['banner_id'], record['stats'])
        docs, dfr = yield dfr

    stats_cache.initialize_keyword_impression_paid_amount()


def update_keyword_impression_paid_amount():
    if stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT is None:
        load_keyword_impression_paid_amount()
    else:
        save_keyword_impression_paid_amount()


@defer.inlineCallbacks
def load_new_banners():
    NEW_BANNERS = {}

    docs, dfr = yield db_utils.get_banners_iter()
    while docs:
        for banner_doc in docs:
            banner_size, banner_id = banner_doc['banner_size'], banner_doc['banner_id']
            campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
            if not campaign_doc:
                continue

            if not stats_utils.is_campaign_active(campaign_doc):
                continue

            if not banner_size in NEW_BANNERS:
                NEW_BANNERS[banner_size] = []
            NEW_BANNERS[banner_size].append(banner_id)
        docs, dfr = yield dfr

    stats_cache.update_new_banners(NEW_BANNERS)


@defer.inlineCallbacks
def recalculate_best_keywords():
    if stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT is None:
        return

    if stats_cache.BANNERS_IMPRESSIONS_COUNT is None:
        return

    #Recalculate database scores
    KEYWORDS_SCORES = {}
    docs, dfr = yield db_utils.get_banner_scores_iter()
    while docs:
        for score_doc in docs:
            banner_id, banner_stats = score_doc['banner_id'], score_doc['stats']

            banner_doc = yield db_utils.get_banner(banner_id)
            if banner_doc is None:
                continue

            campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
            if campaign_doc is None:
                continue

            if not stats_utils.is_campaign_active(campaign_doc):
                continue

            KEYWORDS_SCORES[banner_id] = {}

            for publisher_id in banner_stats:
                if publisher_id not in KEYWORDS_SCORES:
                    KEYWORDS_SCORES[banner_id][publisher_id] = {}

                for keyword, score_value in banner_stats.get(publisher_id, {}).items():
                    last_round_keyword_payment = stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).\
                        get(publisher_id, {}).get(keyword, 0)
                    last_round_impression_count = stats_cache.BANNERS_IMPRESSIONS_COUNT.\
                        get(banner_id, {}).get(publisher_id, 0)

                    last_round_score = 0
                    if last_round_impression_count>0:
                        last_round_score = 1.0*last_round_keyword_payment/last_round_impression_count

                    KEYWORDS_SCORES[banner_id][publisher_id][keyword] = 0.5*score_value + 0.5*last_round_score
        docs, dfr = yield dfr

    #Add scores for new banners
    for banner_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT:
        banner_doc = yield db_utils.get_banner(banner_id)
        if banner_doc is None:
            continue

        campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
        if campaign_doc is None:
            continue

        if not stats_utils.is_campaign_active(campaign_doc):
            continue

        if banner_id not in KEYWORDS_SCORES:
            KEYWORDS_SCORES[banner_id] = {}

        for publisher_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]:
            if publisher_id not in KEYWORDS_SCORES[banner_id]:
                KEYWORDS_SCORES[banner_id][publisher_id] = {}

            for keyword, paid_value in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id].items():
                if keyword in KEYWORDS_SCORES[banner_id][publisher_id]:
                    continue

                impression_count = stats_cache.BANNERS_IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id, 0)
                if impression_count == 0:
                    continue

                KEYWORDS_SCORES[banner_id][publisher_id][keyword] = 1.0*paid_value/impression_count

    for banner_id in KEYWORDS_SCORES:
        yield db_utils.update_banner_scores(banner_id, KEYWORDS_SCORES[banner_id])

    KEYWORDS_BANNERS = {}
    for banner_id in KEYWORDS_SCORES:
        banner = yield db_utils.get_banner(banner_id)

        if not banner:
            print "Warning! Banner %s not in database" %banner_id
            continue

        banner_size = banner['banner_size']
        for publisher_id in KEYWORDS_SCORES[banner_id]:
            if publisher_id not in KEYWORDS_BANNERS:
                KEYWORDS_BANNERS[publisher_id] = {}

            if banner['banner_size'] not in KEYWORDS_BANNERS[publisher_id]:
                KEYWORDS_BANNERS[publisher_id][banner_size] = {}

            for keyword, keyword_score in KEYWORDS_SCORES[banner_id][publisher_id].iteritems():

                if keyword not in KEYWORDS_BANNERS[publisher_id][banner_size]:
                    KEYWORDS_BANNERS[publisher_id][banner_size][keyword] = []

                KEYWORDS_BANNERS[publisher_id][banner_size][keyword].append((keyword_score, banner_id))

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


def clean_database():
    # Remove finished campaigns and associated stats
    pass


def recalculate_stats():
    # Recalculate KEYWORDS_BANNERS and BEST_KEYWORDS
    recalculate_best_keywords()

    # Taking from database BANNERS_IMPRESSIONS_COUNT
    update_banners_impressions_count()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT
    update_keyword_impression_paid_amount()

    # Creating new banners list
    load_new_banners()

    # Clean database task
    clean_database()


def recalculate_stats_task():
    recalculate_stats()
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


def configure_tasks():
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


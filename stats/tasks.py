from twisted.internet import defer, reactor

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
from adselect.db import utils as db_utils


@defer.inlineCallbacks
def save_views():
    # Save BANNERS_IMPRESSIONS_COUNT to database
    for banner_id, counts_per_publisher_dict in stats_cache.IMPRESSIONS_COUNT.iteritems():
        yield db_utils.update_banner_impression_count(banner_id, counts_per_publisher_dict)


@defer.inlineCallbacks
def save_payments():
    # Save stats for KEYWORD_IMPRESSION_PAID_AMOUNT
    for banner_id, payment_stats_dict in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.iteritems():
        banner_stats = yield db_utils.get_banner_payment(banner_id)
        db_banner_stats = banner_stats['stats'] if banner_stats else {}

        for publisher_id, publisher_keywords_payment in payment_stats_dict.items():
            if publisher_id not in db_banner_stats:
                db_banner_stats[publisher_id] = {}

            for keyword, payment_amount in publisher_keywords_payment.items():
                if keyword not in db_banner_stats[publisher_id]:
                    db_banner_stats[publisher_id][keyword] = 0
                db_banner_stats[publisher_id][keyword] += payment_stats_dict[publisher_id][keyword]

        yield db_utils.update_banner_payment(banner_id, db_banner_stats)

        # Clear payment stats for another round
        stats_cache.update_keyword_impression_paid_amount(banner_id, {})


@defer.inlineCallbacks
def save_scores():
    # Recalculate database scores
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

            banner_impression_count = yield db_utils.get_banner_impression_count(banner_id)
            if banner_impression_count is None:
                banner_impression_count = {}

            for publisher_id in banner_stats:
                publisher_db_impression_count = banner_impression_count.get(publisher_id, 0)

                if publisher_id not in KEYWORDS_SCORES:
                    KEYWORDS_SCORES[banner_id][publisher_id] = {}

                for keyword, score_value in banner_stats.get(publisher_id, {}).items():
                    last_round_keyword_payment = stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).\
                        get(publisher_id, {}).get(keyword, 0)

                    impression_count = stats_cache.IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id, 0)
                    last_round_impression_count = max([0, impression_count-publisher_db_impression_count])

                    last_round_score = 0
                    if last_round_impression_count>0:
                        last_round_score = 1.0*last_round_keyword_payment/last_round_impression_count

                    KEYWORDS_SCORES[banner_id][publisher_id][keyword] = 0.5*score_value + 0.5*last_round_score
        docs, dfr = yield dfr

    # Add scores for new banners
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

                impression_count = stats_cache.IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id, 0)
                if impression_count == 0:
                    continue

                KEYWORDS_SCORES[banner_id][publisher_id][keyword] = 1.0*paid_value/impression_count

    for banner_id in KEYWORDS_SCORES:
        yield db_utils.update_banner_scores(banner_id, KEYWORDS_SCORES[banner_id])

    defer.returnValue(KEYWORDS_SCORES)


def clean_database():
    # Remove finished campaigns and associated stats.
    pass


@defer.inlineCallbacks
def recalculate_stats():
    from adselect.stats import utils as stats_utils

    # Recalculate KEYWORDS_BANNERS and BEST_KEYWORDS.
    SCORES_STATS = yield save_scores()

    # Taking from database BANNERS_IMPRESSIONS_COUNT.
    yield save_views()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT.
    yield save_payments()

    # Load banners.
    yield stats_utils.load_banners()

    # Load scores
    yield stats_utils.load_scores(SCORES_STATS)

    # Clean database task.
    clean_database()


def recalculate_stats_task():
    recalculate_stats()
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


def configure_tasks():
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


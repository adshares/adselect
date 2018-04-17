from twisted.internet import defer, reactor

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
from adselect.db import utils as db_utils


@defer.inlineCallbacks
def save_views():
    """
    Save impression count data from cache to database.

    :return:
    """
    # Save BANNERS_IMPRESSIONS_COUNT to database
    for banner_id, counts_per_publisher_dict in stats_cache.IMPRESSIONS_COUNT.iteritems():
        yield db_utils.update_banner_impression_count(banner_id, counts_per_publisher_dict)


@defer.inlineCallbacks
def save_payments():
    """
    Save payment per keywords data from cache to database.

    :return:
    """
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
        del stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]


@defer.inlineCallbacks
def save_scores():
    """
    Save scores.
    1. Get scores from database.
    2. Validate active banners.
    3. For each banner get impression count

    :return:
    """
    db_banners = set()

    # Recalculate database scores
    docs, dfr = yield db_utils.get_banner_scores_iter()
    while docs:
        for score_doc in docs:
            banner_id, banner_stats = score_doc['banner_id'], score_doc['stats']

            if not stats_utils.is_banner_live(banner_id):
                continue

            banner_scores = {}

            banner_impression_count = yield db_utils.get_banner_impression_count(banner_id)
            if banner_impression_count is None:
                banner_impression_count = {}

            for publisher_id in banner_stats:
                publisher_db_impression_count = banner_impression_count.get(publisher_id, 0)

                banner_scores[publisher_id] = {}

                for keyword, score_value in banner_stats.get(publisher_id, {}).items():
                    last_round_keyword_payment = stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][keyword]

                    impression_count = stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id]
                    last_round_impression_count = max([0, impression_count - publisher_db_impression_count])

                    last_round_score = 0
                    if last_round_impression_count > 0:
                        last_round_score = 1.0 * last_round_keyword_payment / last_round_impression_count

                    banner_scores[publisher_id][keyword] = 0.5 * score_value + 0.5 * last_round_score

            yield db_utils.update_banner_scores(banner_id, banner_scores)
            db_banners |= {banner_id}
        docs, dfr = yield dfr

    # Add scores for new banners
    for banner_id in set(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.keys()) - db_banners:
        if not stats_utils.is_banner_live(banner_id):
            continue

        banner_scores = {}

        for publisher_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id].keys():
            banner_scores[publisher_id] = {}

            for keyword in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id].keys():
                paid_value = stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][keyword]

                impression_count = stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id]
                if impression_count == 0:
                    continue

                banner_scores[publisher_id][keyword] = 1.0 * paid_value / impression_count
        yield db_utils.update_banner_scores(banner_id, banner_scores)


@defer.inlineCallbacks
def clean_database():
    """
    Remove finished campaigns and associated stats.

    :return:
    """
    docs, dfr = yield db_utils.get_campaigns_iter()
    while docs:
        for campaign_doc in docs:
            campaign_id = campaign_doc['campaign_id']

            if not stats_utils.is_campaign_active(campaign_doc):
                campaigns_banners = yield db_utils.get_campaign_banners(campaign_id)
                for banner_doc in campaigns_banners:
                    banner_id = banner_doc['banner_id']

                    # remove payments_stats
                    db_utils.delete_banner_payments(banner_id)

                    # remove impression_stats
                    db_utils.delete_banner_impression_count(banner_id)
                    stats_cache.delete_impression_count(banner_id)

                    # remove scores stats
                    db_utils.delete_banner_scores(banner_id)

                # remove banners
                db_utils.delete_campaign_banners(campaign_id)

                # remove campaign
                db_utils.delete_campaign(campaign_id)

        docs, dfr = yield dfr


@defer.inlineCallbacks
def recalculate_stats():
    """
    Dump all data from cache to database, reload cache data and recalculate everything.

    :return:
    """
    # Recalculate KEYWORDS_BANNERS and BEST_KEYWORDS.
    scores_stats = yield save_scores()

    # Taking from database BANNERS_IMPRESSIONS_COUNT.
    yield save_views()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT.
    yield save_payments()

    # Load banners.
    yield stats_utils.load_banners()

    # Load scores
    yield stats_utils.load_scores(scores_stats)

    # Clean database task.
    yield clean_database()

    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats)


def configure_tasks():
    """
    Recalculate stats.

    :return:
    """
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats)

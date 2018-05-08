from collections import defaultdict

from twisted.internet import defer, reactor
from twisted.logger import Logger

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
from adselect.db import utils as db_utils
import logging
logging.basicConfig(level=logging.INFO)


@defer.inlineCallbacks
def save_impression_count():
    """
    Save impression count data from cache to database.

    :return:
    """
    # Save BANNERS_IMPRESSIONS_COUNT to database
    for banner_id, counts_per_publisher_dict in stats_cache.IMPRESSIONS_COUNT.items():
        yield db_utils.update_banner_impression_count(banner_id, counts_per_publisher_dict)


@defer.inlineCallbacks
def save_keyword_payments():
    """
    Save payment per keywords data from cache to database.

    :return:
    """
    # Save stats for KEYWORD_IMPRESSION_PAID_AMOUNT
    for banner_id, payment_stats_dict in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.iteritems():

        db_banner_stats = defaultdict(lambda: defaultdict(lambda: float(0.0)))

        banner_stats = yield db_utils.get_banner_payment(banner_id)
        if banner_stats:
            db_banner_stats.update(banner_stats['stats'])

        for publisher_id, publisher_keywords_payment in payment_stats_dict.items():
            for keyword, payment_amount in publisher_keywords_payment.items():
                db_banner_stats[publisher_id][keyword] += payment_stats_dict[publisher_id][keyword]

        yield db_utils.update_banner_payment(banner_id, db_banner_stats)

    # Clear payment stats for another round
    for banner_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.keys():
        del stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]


@defer.inlineCallbacks
def save_banner_scores():
    """
    Save scores.
    1. Get scores from database.
    2. Validate active banners.
    3. For each banner get impression count

    :return:
    """
    db_banners = set()

    # Recalculate database scores
    @defer.inlineCallbacks
    def update_score(score_doc):

        banner_id, banner_stats = score_doc['banner_id'], score_doc['stats']

        banner_scores = defaultdict(dict)

        for publisher_id in banner_stats:

            for keyword, score_value in banner_stats.get(publisher_id, {}).iteritems():

                last_round_score = yield calculate_last_round_score(publisher_id, banner_id, keyword)

                banner_scores[publisher_id][keyword] = 0.5 * score_value + 0.5 * last_round_score

        yield db_utils.update_banner_scores(banner_id, banner_scores)
        db_banners.update(banner_id)

    stats_utils.iterate_deferred(db_utils.get_collection_iter('scores_stats'), update_score)

    yield save_new_banner_scores(db_banners)


@defer.inlineCallbacks
def calculate_last_round_score(publisher_id, banner_id, keyword):
    """

    :param publisher_id:
    :param banner_id:
    :param keyword:
    :return:
    """

    banner_impression_count = yield db_utils.get_banner_impression_count(banner_id)
    if not banner_impression_count:
        banner_impression_count = defaultdict(lambda: int(0))

    publisher_db_impression_count = banner_impression_count[publisher_id]

    last_round_keyword_payment = stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][keyword]

    impression_count = stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id]
    last_round_impression_count = max([0, impression_count - publisher_db_impression_count])

    if last_round_impression_count > 0:
        defer.returnValue(1.0 * last_round_keyword_payment / last_round_impression_count)

    defer.returnValue(0.0)


@defer.inlineCallbacks
def save_new_banner_scores(db_banners=None):
    """
    Save scores for new banners

    :param db_banners: Set of banners already scored.
    :return:
    """
    # Add scores for new banners

    if not db_banners:
        db_banners = set()

    for banner_id in set(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.keys()) - db_banners:

        active = yield stats_utils.is_banner_active(banner_id)
        if not active:
            continue

        banner_scores = defaultdict(dict)

        for publisher_id in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id].keys():
            for keyword, paid_value in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id].iteritems():
                impression_count = stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id]
                if not impression_count == 0:
                    banner_scores[publisher_id][keyword] = 1.0 * paid_value / impression_count
        yield db_utils.update_banner_scores(banner_id, banner_scores)


@defer.inlineCallbacks
def clean_database():
    """
    Remove finished campaigns and associated stats.

    :return:
    """
    docs, dfr = yield db_utils.get_collection_iter('campaign')
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
    scores_stats = yield save_banner_scores()

    # Taking from database BANNERS_IMPRESSIONS_COUNT.
    yield save_impression_count()

    # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT.
    yield save_keyword_payments()

    # Load banners.
    yield stats_utils.load_banners()

    # Load scores
    yield stats_utils.load_scores(scores_stats)

    # Clean database task.
    yield clean_database()

    configure_tasks()


def configure_tasks():
    """
    Recalculate stats.

    :return:
    """
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats)

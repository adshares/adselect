from twisted.internet import defer, reactor

from adselect.contrib import utils as contrib_utils
from adselect.stats import const as stats_consts
from adselect.db import utils as db_utils
from adselect.stats import cache as stats_cache


def is_campaign_active(campaign_doc):
    timestamp = contrib_utils.get_timestamp()

    # Campaign will not start in this round
    if campaign_doc['time_start'] > timestamp + stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL:
        return False

    # Campaign is finished
    if campaign_doc['time_end'] <= timestamp:
        return False

    return True


@defer.inlineCallbacks
def load_banners():
    """Load only active banners to cache."""
    BANNERS = {}

    docs, dfr = yield db_utils.get_banners_iter()
    while docs:
        for banner_doc in docs:
            banner_size, banner_id = banner_doc['banner_size'], banner_doc['banner_id']
            campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
            if not campaign_doc:
                continue

            if not is_campaign_active(campaign_doc):
                continue

            if not banner_size in BANNERS:
                BANNERS[banner_size] = []

            BANNERS[banner_size].append(banner_id)

        docs, dfr = yield dfr
    stats_cache.update_banners(BANNERS)


@defer.inlineCallbacks
def load_impression_counts():
    """Load impressions/events counts to cache."""

    docs, dfr = yield db_utils.get_banner_impression_count_iter()
    while docs:
        for stats_doc in docs:
            banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
            stats_cache.update_impressions_count(banner_id, stats)
        docs, dfr = yield dfr


@defer.inlineCallbacks
def load_scores(SCORES_DB_STATS = None):
    """Load best paid keywords taking into account scores"""

    if SCORES_DB_STATS is None:
        SCORES_DB_STATS = {}

        docs, dfr = yield db_utils.get_banner_scores_iter()
        while docs:
            for stats_doc in docs:
                banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
                SCORES_DB_STATS[banner_id] = stats

            docs, dfr = yield dfr

    KEYWORDS_BANNERS = {}
    for banner_id in SCORES_DB_STATS:
        banner = yield db_utils.get_banner(banner_id)

        if not banner:
            print "Warning! Banner %s not in database" %banner_id
            continue

        banner_size = banner['banner_size']
        for publisher_id in SCORES_DB_STATS[banner_id]:
            if publisher_id not in KEYWORDS_BANNERS:
                KEYWORDS_BANNERS[publisher_id] = {}

            if banner['banner_size'] not in KEYWORDS_BANNERS[publisher_id]:
                KEYWORDS_BANNERS[publisher_id][banner_size] = {}

            for keyword, keyword_score in SCORES_DB_STATS[banner_id][publisher_id].iteritems():

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
    for publisher_id in stats_cache.KEYWORDS_BANNERS:
        BEST_KEYWORDS[publisher_id] = {}

        for size in stats_cache.KEYWORDS_BANNERS[publisher_id]:
            BEST_KEYWORDS[publisher_id][size] = []

            for keyword, banners_list in stats_cache.KEYWORDS_BANNERS[publisher_id][size].iteritems():
                if not banners_list:
                    continue

                BEST_KEYWORDS[publisher_id][size].append((banners_list[0][0], keyword))

            BEST_KEYWORDS[publisher_id][size] = sorted(BEST_KEYWORDS[publisher_id][size], reverse=True)
            BEST_KEYWORDS[publisher_id][size] = [elem[1] for elem in BEST_KEYWORDS[publisher_id][size]]
    stats_cache.update_best_keywords(BEST_KEYWORDS)


@defer.inlineCallbacks
def initialize_stats():
    from adselect.stats import tasks as stats_task

    # Load all banners to show randomly new banners.
    yield load_banners()

    # We have to load all impressions to keep information whether we can/can't display new banners for publisher.
    yield load_impression_counts()

    # We do not load payments as it is kept per calculation round.

    # Load best keywords taking into account scores.
    yield load_scores()
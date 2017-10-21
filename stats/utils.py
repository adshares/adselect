from twisted.internet import defer
import random

from adselect.contrib import utils as contrib_utils
from adselect.stats import const as stats_consts
from adselect.db import utils as db_utils
from adselect.stats import cache as stats_cache


def genkey(key, val, delimiter="_"):
    keywal = "%s%s%s" % (key, delimiter, val)
    return keywal.replace(".", "")


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
    docs, dfr = yield db_utils.get_banners_iter()
    while docs:
        for banner_doc in docs:
            banner_size, banner_id = banner_doc['banner_size'], banner_doc['banner_id']
            campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
            if not campaign_doc:
                continue

            if not is_campaign_active(campaign_doc):
                continue

            stats_cache.add_banner(banner_id, banner_size)
        docs, dfr = yield dfr


@defer.inlineCallbacks
def load_impression_counts():
    """Load impressions/events counts to cache."""

    docs, dfr = yield db_utils.get_banner_impression_count_iter()
    while docs:
        for stats_doc in docs:
            banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
            for publisher_id, value in stats.iteritems():
                stats_cache.set_impression_count(banner_id, publisher_id, value)
        docs, dfr = yield dfr


@defer.inlineCallbacks
def load_scores(scores_db_stats=None):
    """Load best paid keywords taking into account scores"""

    # TODO: need optimalization.
    if scores_db_stats is None:
        scores_db_stats = {}

        docs, dfr = yield db_utils.get_banner_scores_iter()
        while docs:
            for stats_doc in docs:
                banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
                scores_db_stats[banner_id] = stats

            docs, dfr = yield dfr

    keywords_banners = {}
    for banner_id in scores_db_stats:
        banner = yield db_utils.get_banner(banner_id)

        if not banner:
            print "Warning! Banner %s not in database" % banner_id
            continue

        banner_size = banner['banner_size']
        for publisher_id in scores_db_stats[banner_id]:
            if publisher_id not in keywords_banners:
                keywords_banners[publisher_id] = {}

            if banner['banner_size'] not in keywords_banners[publisher_id]:
                keywords_banners[publisher_id][banner_size] = {}

            for keyword, keyword_score in scores_db_stats[banner_id][publisher_id].iteritems():

                if keyword not in keywords_banners[publisher_id][banner_size]:
                    keywords_banners[publisher_id][banner_size][keyword] = []

                keywords_banners[publisher_id][banner_size][keyword].append((keyword_score, banner_id))

    for publisher_id in keywords_banners:
        for banner_size in keywords_banners[publisher_id]:
            for keyword in keywords_banners[publisher_id][banner_size]:
                keywords_banners[publisher_id][banner_size][keyword] = \
                    sorted(keywords_banners[publisher_id][banner_size][keyword], reverse=True)
    stats_cache.set_keywords_banners(keywords_banners)

    for publisher_id in keywords_banners:
        for banner_size in keywords_banners[publisher_id]:
            keywords_list = []
            for keyword, banners_list in keywords_banners[publisher_id][banner_size].iteritems():
                if not banners_list:
                    continue

                keywords_list.append((banners_list[0][0], keyword))

            keywords_list = sorted(keywords_list, reverse=True)
            stats_cache.set_best_keywords(publisher_id, banner_size, [elem[1] for elem in keywords_list])


@defer.inlineCallbacks
def initialize_stats():
    # Load all banners to show randomly new banners.
    yield load_banners()

    # We have to load all impressions to keep information whether we can/can't display new banners for publisher.
    yield load_impression_counts()

    # We do not load payments as it is kept per calculation round.

    # Load best keywords taking into account scores.
    yield load_scores()


def select_new_banners(publisher_id,
                       banner_size,
                       proposition_nb,
                       notpaid_display_cutoff=stats_consts.NEW_BANNERS_IMRESSION_CUTOFF,
                       filtering_population_factor=4
                       ):
    """
        Return banners ids without payment statistic.
        The function doesn't allow to display banners more than notpaid_display_cutoff times without payment.
        publisher_id - publisher id
    """

    new_banners = stats_cache.get_banners(banner_size)
    random_banners = []
    for i in range(proposition_nb * filtering_population_factor):
        random_banners.append(random.choice(new_banners))

    # Filter selected banners out banners witch were displayed more times than notpaid_display_cutoff
    selected_banners = []
    for banner_id in random_banners:
        if stats_cache.get_impression_count(banner_id, publisher_id) < notpaid_display_cutoff:
            selected_banners.append(banner_id)

        if len(selected_banners) > proposition_nb:
            break

    return selected_banners[:proposition_nb]


def select_best_banners(publisher_id,
                        banner_size,
                        impression_keywords_dict,
                        propositions_nb=100,
                        best_keywords_cutoff=100,
                        banners_per_keyword_cutoff=10,
                        mixed_new_banners_percent=5
                        ):
    """
        Select banners with appropriate size for given impression keywords.
        proposition_nb - the amount of selected banners
        publisher_id - publisher id
        best_keywords_cutoff - cutoff of the best paid keywords taking into account
        banners_per_keyword_cutoff - cutoff of the banners numbers in every seleted keywords
        mixed_new_banners_percent - approximate percentage of new banners in proposed banners list
    """
    # selected best paid impression keywords
    publisher_best_keys = stats_cache.get_best_keywords(publisher_id, banner_size)[:best_keywords_cutoff]
    sbpik = set([genkey(*item) for item in impression_keywords_dict.items()]) & set(publisher_best_keys)

    # Select best paid banners with appropriate size
    selected_banners = []
    selected_banners_count = 0

    publisher_banners = stats_cache.get_publisher_banners(publisher_id, banner_size)
    for avg_price, banner_id in contrib_utils.merge(
            *[publisher_banners.get(keyword, [])[:banners_per_keyword_cutoff] for keyword in sbpik]
    ):

        selected_banners.append(banner_id)
        selected_banners_count += 1

        if selected_banners_count >= propositions_nb:
            break

    # Add new banners without payment statistic
    new_banners_proposition_nb = int(mixed_new_banners_percent * propositions_nb / 100.0)
    selected_banners += select_new_banners(publisher_id, banner_size, new_banners_proposition_nb)
    random.shuffle(selected_banners)

    # Shuffle items in the list
    return selected_banners[:propositions_nb]


def update_impression(banner_id, publisher_id, impression_keywords, paid_amount):
    # Update BANNERS_IMPRESSIONS_COUNT
    stats_cache.inc_impression_count(banner_id, publisher_id, 1)

    # Update KEYWORD_IMPRESSION_PAID_AMOUNT if paid_amount > 0
    if not paid_amount > 0:
        return

    for key, val in impression_keywords.items():
        stat_key = genkey(key, val)
        stats_cache.inc_keyword_impression_paid_amount(banner_id, publisher_id, stat_key, paid_amount)

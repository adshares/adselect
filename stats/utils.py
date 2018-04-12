from twisted.internet import defer
import random

from adselect.contrib import utils as contrib_utils
from adselect.stats import const as stats_consts
from adselect.db import utils as db_utils
from adselect.stats import cache as stats_cache
import logging


def genkey(key, val, delimiter="_"):
    """
    Generate keyword identifier, ex. {'animal': 'dog'} becomes 'animal_dog'

    :param key: Key
    :param val: Value
    :param delimiter: Delimiter, default "_"
    :return: Generated identifier
    """
    keywal = "%s%s%s" % (key, delimiter, val)
    return keywal.replace(".", "")


def is_campaign_active(campaign_doc):
    """
    Compare campaign's start and end times with current time.

    :param campaign_doc: Campaign document.
    :return: True for active campaigns, False for inactive.
    """

    timestamp = contrib_utils.get_timestamp()

    # Campaign will not start in this round
    if campaign_doc['time_start'] > timestamp + stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL:
        return False

    # Campaign is finished
    if campaign_doc['time_end'] <= timestamp:
        return False

    return True


@defer.inlineCallbacks
def is_banner_active(banner_doc):
    """
    Check if banner's campaign is still active.

    :param banner_doc: Banner document
    :return: True for active, False for inactive.
    """

    campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
    if campaign_doc and is_campaign_active(campaign_doc):
        defer.returnValue(True)

    defer.returnValue(False)


@defer.inlineCallbacks
def load_banners():
    """
    Load only active banners to cache.
    """

    docs, dfr = yield db_utils.get_collection_iter('banner')
    while docs:
        for banner_doc in docs:
            active = yield is_banner_active(banner_doc)
            active = True
            if active:
                banner_size, banner_id = banner_doc['banner_size'], banner_doc['banner_id']
                stats_cache.add_banner(banner_id, banner_size)
        docs, dfr = yield dfr


@defer.inlineCallbacks
def load_impression_counts():
    """
    Load impressions/events counts to cache.
    """

    docs, dfr = yield db_utils.get_banner_impression_count_iter()
    while docs:
        for stats_doc in docs:
            banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
            for publisher_id, value in stats.iteritems():
                stats_cache.set_impression_count(banner_id, publisher_id, value)
        docs, dfr = yield dfr


@defer.inlineCallbacks
def load_scores(scores_db_stats=None):
    """
    Load best paid keywords taking into account scores
    """

    if scores_db_stats is None:
        scores_db_stats = {}

        docs, dfr = yield db_utils.get_banner_scores_iter()
        while docs:
            for stats_doc in docs:
                banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
                scores_db_stats[banner_id] = stats

            docs, dfr = yield dfr

    best_keywords = {}
    for banner_id in scores_db_stats:
        banner = yield db_utils.get_banner(banner_id)

        if not banner:
            print "Warning! Banner %s not in database" % banner_id
            continue

        banner_size = banner['banner_size']
        for publisher_id in scores_db_stats[banner_id]:
            if publisher_id not in best_keywords:
                best_keywords[publisher_id] = {}

            if banner_size not in best_keywords[publisher_id]:
                best_keywords[publisher_id][banner_size] = {}

            for keyword, keyword_score in scores_db_stats[banner_id][publisher_id].iteritems():
                stats_cache.add_keyword_banner(publisher_id, banner_size, keyword, keyword_score, banner_id)

                best_keywords[publisher_id][banner_size][keyword] = max(
                    [keyword_score, best_keywords[publisher_id][banner_size].get(keyword, 0)])

    for publisher_id in best_keywords:
        for banner_size in best_keywords[publisher_id]:

            keywords_list = []
            for keyword, keyword_score in best_keywords[publisher_id][banner_size].iteritems():
                keywords_list.append((keyword_score, keyword))

            keywords_list = sorted(keywords_list, reverse=True)
            stats_cache.set_best_keywords(publisher_id, banner_size, [elem[1] for elem in keywords_list])


@defer.inlineCallbacks
def initialize_stats():
    """
    Initialize data cache.

    :return:
    """
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
                       notpaid_display_cutoff=stats_consts.NEW_BANNERS_IMPRESSION_CUTOFF,
                       filtering_population_factor=4
                       ):
    """
    Return banners ids without payment statistic.

    The function doesn't allow to display banners more than notpaid_display_cutoff times without payment.

    :param publisher_id:
    :param banner_size:
    :param proposition_nb:
    :param notpaid_display_cutoff:
    :param filtering_population_factor:
    :return:
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

    :param publisher_id:
    :param banner_size:
    :param impression_keywords_dict:
    :param propositions_nb: the amount of selected banners (default: 100)
    :param best_keywords_cutoff: cutoff of the best paid keywords taking into account
    :param banners_per_keyword_cutoff: cutoff of the banners numbers in every seleted keywords
    :param mixed_new_banners_percent: approximate percentage of new banners in proposed banners list
    :return:
    """
    # selected best paid impression keywords
    publisher_best_keys = stats_cache.get_best_keywords(publisher_id, banner_size)[:best_keywords_cutoff]
    sbpik = set([genkey(*item) for item in impression_keywords_dict.items()]) & set(publisher_best_keys)

    # Select best paid banners with appropriate size
    selected_banners = []
    selected_banners_count = 0

    publisher_banners = stats_cache.get_keyword_banners(publisher_id, banner_size)
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
    """
    Update impression cache.

    1. Increase impression count
    2. If paid, Update keyword paid amount.

    :param banner_id:
    :param publisher_id:
    :param impression_keywords:
    :param paid_amount:
    :return:
    """


    logger = logging.getLogger(__name__)

    # Update BANNERS_IMPRESSIONS_COUNT
    stats_cache.inc_impression_count(banner_id, publisher_id, 1)

    # Update KEYWORD_IMPRESSION_PAID_AMOUNT if paid_amount > 0
    if not paid_amount > 0:
        return

    for key, val in impression_keywords.items():
        stat_key = genkey(key, val)
        stats_cache.inc_keyword_impression_paid_amount(banner_id, publisher_id, stat_key, paid_amount)

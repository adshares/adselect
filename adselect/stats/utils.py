from __future__ import print_function
import random
from collections import defaultdict

from twisted.internet import defer

from adselect.contrib import utils as contrib_utils
from adselect.stats import const as stats_consts
from adselect.db import utils as db_utils
from adselect.stats import cache as stats_cache


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
def is_banner_live(banner_id):

    banner_doc = yield db_utils.get_banner(banner_id)
    if not banner_doc:
        defer.returnValue(False)

    campaign_doc = yield db_utils.get_campaign(banner_doc['campaign_id'])
    if not campaign_doc:
        defer.returnValue(False)

    if not is_campaign_active(campaign_doc):
        defer.returnValue(False)

    defer.returnValue(True)


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
def iterate_deferred(deferred, func):
    data, dfr = yield deferred

    while data:
        for data_element in data:
            yield func(data_element)
        data, dfr = yield dfr


@defer.inlineCallbacks
def load_banners():
    """
    Load only active banners to cache.
    """

    @defer.inlineCallbacks
    def func(banner_doc):
        active = yield is_banner_active(banner_doc)
        if active:
            banner_size, banner_id = banner_doc['banner_size'], banner_doc['banner_id']
            stats_cache.BANNERS[banner_size].append(banner_id)

    yield iterate_deferred(db_utils.get_collection_iter('banner'), func)


@defer.inlineCallbacks
def load_impression_counts():
    """
    Load impressions/events counts to cache.
    """

    docs, dfr = yield db_utils.get_collection_iter('banner')
    while docs:
        for stats_doc in docs:
            banner_id, stats = stats_doc['banner_id'], stats_doc['stats']
            for publisher_id, value in stats.iteritems():
                stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id] = value
        docs, dfr = yield dfr


@defer.inlineCallbacks
def load_scores(scores_db_stats=None):
    """
    Load best paid keywords taking into account scores.

    1. Get banner scores.
    2. For each one, get banner.
    3. Get

    :param scores_db_stats:
    """

    if scores_db_stats is None:
        scores_db_stats = {}

        @defer.inlineCallbacks
        def func(stats_docs):
            for stats_doc in stats_docs:
                bannerid, stats = stats_doc['banner_id'], stats_doc['stats']
                scores_db_stats[bannerid] = stats

        yield iterate_deferred(db_utils.get_collection_iter('banner_scores'), func)

    best_keywords = defaultdict(lambda: defaultdict(lambda: defaultdict(int)))

    for banner_id in scores_db_stats:
        banner = yield db_utils.get_banner(banner_id)

        if not banner:
            print("Warning! Banner %s not in database" % banner_id)
            continue

        banner_size = banner['banner_size']
        for publisher_id in scores_db_stats[banner_id]:

            for keyword, keyword_score in scores_db_stats[banner_id][publisher_id].iteritems():
                stats_cache.add_keyword_banner(publisher_id, banner_size, keyword, keyword_score, banner_id)

                best_keywords[publisher_id][banner_size][keyword] = max(
                    [keyword_score, best_keywords[publisher_id][banner_size][keyword]])

    for publisher_id in best_keywords:
        for banner_size in best_keywords[publisher_id]:

            keywords_list = [(keyword_score, keyword) for keyword, keyword_score
                             in best_keywords[publisher_id][banner_size].iteritems()]
            keywords_list = sorted(keywords_list, reverse=True)
            stats_cache.BEST_KEYWORDS[publisher_id][banner_size] = [elem[1] for elem in keywords_list]


@defer.inlineCallbacks
def initialize_stats():
    """
    Initialize data cache. Load data from the database into memory.

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
                       filtering_population_factor=4):
    """
    Return banners ids without payment statistic.

    The function doesn't allow to display banners more than *notpaid_display_cutoff* times without payment.

    1. Get banners with the right size.
    2. Choose random banners from that population.
    3. Filter out banners which were displayed less times than *notpaid_display_cutoff*
    4. Return chosen banners.

    :param publisher_id: Publisher identifier.
    :param banner_size: Banner size (width x height) in string format.
    :param proposition_nb: The amount of returned banners.
    :param notpaid_display_cutoff:
    :param filtering_population_factor: Random population sample.
    :return: List of banners.
    """
    all_banners = stats_cache.BANNERS[banner_size]
    random_banner_number = proposition_nb * filtering_population_factor
    random_banners = random.sample(all_banners, random_banner_number)

    # Filter selected banners out banners witch were displayed more times than notpaid_display_cutoff
    selected_banners = []
    for banner_id in random_banners:
        if stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id] < notpaid_display_cutoff:
            selected_banners.append(banner_id)

        if len(selected_banners) == proposition_nb:
            break

    return selected_banners


def select_best_banners(publisher_id,
                        banner_size,
                        impression_keywords_dict,
                        propositions_nb=100,
                        best_keywords_cutoff=100,
                        banners_per_keyword_cutoff=10,
                        mixed_new_banners_percent=5):
    """
    Select banners with appropriate size for given keywords.

    1. Get best paid keywords (limited to a cutoff value)
    2. Find common keywords from given keywords and best paid keywords.
    3. Find best paid banners for give keywords and size.
    4. Add new banners, which have no payments statistic yet.
    5. Shuffle the banners.
    6. Return a list of banners, size limited to the defined cutoff value.

    :param publisher_id: Publisher identifier.
    :param banner_size: Banner size (width x height) in string format.
    :param impression_keywords_dict: Dictionary of keywords for the request.
    :param propositions_nb: The amount of returned banners.
    :param best_keywords_cutoff: Cutoff of the number of best paid keywords taking into account.
    :param banners_per_keyword_cutoff: Cutoff of the banners number in every selected keywords.
    :param mixed_new_banners_percent: Approximate percentage of new banners in proposed banners list.
    :return: List of banners.
    """
    # selected best paid impression keywords
    publisher_best_keys = stats_cache.BEST_KEYWORDS[publisher_id][banner_size][:best_keywords_cutoff]

    sbpik = set([genkey(key, value) for key, value in impression_keywords_dict.items()]) & set(publisher_best_keys)

    # Select best paid banners with appropriate size
    selected_banners = []
    selected_banners_count = 0

    publisher_banners = stats_cache.KEYWORDS_BANNERS['publisher_id']['banner_size']
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

    1. Increase impression count.
    2. If paid (paid > 0), update keyword paid amount.

    :param banner_id: Banner identifier.
    :param publisher_id: Publisher identifier.
    :param impression_keywords: Dictionary of keywords (with values).
    :param paid_amount: Amount paid for the impression.
    :return:
    """

    # Update BANNERS_IMPRESSIONS_COUNT
    stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id] += 1

    # Update KEYWORD_IMPRESSION_PAID_AMOUNT if paid_amount > 0
    if paid_amount > 0:

        for key, val in impression_keywords.items():
            stat_key = genkey(key, val)
            stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][stat_key] += 1

from collections import defaultdict
from adselect.contrib import utils as contrib_utils

#: Keep info about best paid keywords for the specific banner size. Keywords in the list are ordered from the best paid
#:
#: BEST_KEYWORDS: { 'publisher_id1':
#:    { 'size1':[keyword1, keyword2, ...], 'size2':[keyword1, keyword2, ...] }
#:    }
BEST_KEYWORDS = defaultdict(lambda: defaultdict(list))


def set_best_keywords(publisher_id, banner_size, keywords_list):
    """
    Sets the best keywords per publisher per banner size.

    :param publisher_id: Publisher identifier.
    :param banner_size: Banner size.
    :param keywords_list: List of keywords.
    :return: None
    """
    BEST_KEYWORDS[publisher_id][banner_size] = keywords_list


def get_best_keywords(publisher_id, banner_size):
    """

    :param publisher_id: Publisher identifier.
    :param banner_size: Banner size.
    :return: List of best keywords or empty list.
    """
    return BEST_KEYWORDS.get(publisher_id, {}).get(banner_size, [])


def delete_best_keywords():
    """
    Reset the BEST_KEYOWRDS cache to empty.

    :return: None
    """
    global BEST_KEYWORDS
    BEST_KEYWORDS = defaultdict(lambda: defaultdict(list))


#: KEYWORDS_BANNERS keeps sorted list of banners for given size and keyword
#:
#: KEYWORDS_BANNERS = {
#:   'publisher_id1': {
#:      'size1': {
#:          keyword1: [(pay_score, campaignid1_bannerid1),
#:                       (pay_score, campaignid2_bannerid2), ...],
#:          keyword2: [(pay_score, campaignid1_bannerid1),
#:                        (pay_score, campaignid2_bannerid2), ..., ...],
#:       },
#:       'size2':{
#:          keyword1: [(pay_score, campaignid1_bannerid1),
#:                        (pay_score, campaignid2_bannerid2), ...]
#:       }
#:   },
#:   'publisher_id2': { ... }
#: }
KEYWORDS_BANNERS = defaultdict(lambda: defaultdict(lambda: defaultdict(list)))


def add_keyword_banner(publisher_id, banner_size, keyword, keyword_score, banner_id, limit=100):

    contrib_utils.reverse_insort(KEYWORDS_BANNERS[publisher_id][banner_size][keyword], (keyword_score, banner_id))
    KEYWORDS_BANNERS[publisher_id][banner_size][keyword] = KEYWORDS_BANNERS[publisher_id][banner_size][keyword][:limit]


def get_keyword_banners(publisher_id, banner_size):
    """

    :param publisher_id: Publisher identifier.
    :param banner_size:  Banner size.
    :return: List of keywords or empty list.
    """
    return KEYWORDS_BANNERS.get(publisher_id, {}).get(banner_size, [])


def reset_keyword_banners():
    """
    Reset the KEYWORDS_BANNERS cache to empty.

    :return: None
    """
    global KEYWORDS_BANNERS
    KEYWORDS_BANNERS = defaultdict(lambda: defaultdict(lambda: defaultdict(list)))


#: Keep info about last round impression payments > 0
#: KEYWORD_IMPRESSION_PAID_AMOUNT = {
#:   'campaignid2_bannerid2': {
#:       'publisher_id_1': {
#:           'keyword1': 'total_payment_amount',
#:           'keyword2': 'total_payment_amount',
#:       },
#:       'publisher_id_2': {
#:           'keyword1': 'total_payment_amount',
#:           'keyword2': 'total_payment_amount',
#:       }
#:   }
#:  }
KEYWORD_IMPRESSION_PAID_AMOUNT = defaultdict(lambda: defaultdict(lambda: defaultdict(lambda: float(0.0))))


def set_keyword_impression_paid_amount(banner_id, stats):
    """
    Set impressions paid amount.

    :param banner_id: Banner identifier.
    :param stats: [publisher_id][keyword] Dictionary.
    :return:
    """
    KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id] = stats


def inc_keyword_impression_paid_amount(banner_id, publisher_id, keyword, value):
    """
    Increment value of paid amount

    :param banner_id: Banner identifier
    :param publisher_id: Publisher identifier
    :param keyword: Keyword value
    :param value: Increment value
    :return: None
    """
    KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][keyword] += value


def get_keyword_impression_paid_amount_iter():
    """

    :return: Iterable
    """
    return KEYWORD_IMPRESSION_PAID_AMOUNT.iteritems()


def get_keyword_impression_paid_amount(banner_id, publisher_id, keyword):
    """

    :param banner_id: Banner identifier.
    :param publisher_id: Publisher identifier.
    :param keyword: Keyword
    :return: Total paid amount
    """
    return KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).get(publisher_id, {}).get(keyword, 0.0)


def get_last_round_paid_banners():
    """

    :return: Banner identifiers in last round.
    """
    return KEYWORD_IMPRESSION_PAID_AMOUNT.keys()


def get_last_round_paid_banner_publishers(banner_id):
    """

    :param banner_id: Banner identifier
    :return: Publishers paid in last round.
    """
    return KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).keys()


def get_last_round_paid_banner_publisher_keywords(banner_id, publisher_id):
    """

    :param banner_id: Banner identifiers.
    :param publisher_id: Publisher identifier.
    :return: Keywords paid in last round.
    """
    return KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).get(publisher_id, {}).keys()


#: Keep data about total impressions count of banners
#:
#: IMPRESSIONS_COUNT = {
#:   'campaignid1_bannerid1': {
#:           'publisher_id1': 'impression_count_for_publisher_1',
#:           'publisher_id2': 'impression_count_for_publisher_2'
#:    },
#:  'campaignid2_bannerid2': { ... }
#: }
IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(int))


def set_impression_count(banner_id, publisher_id, value):
    """
    Sets impression count to *value*

    :param banner_id: Banner identifier.
    :param publisher_id: Publisher identifier.
    :param value: New value.
    :return:
    """
    IMPRESSIONS_COUNT[banner_id][publisher_id] = value


def inc_impression_count(banner_id, publisher_id, value=1):
    """
    Increases impression count.

    :param banner_id: Banner identifier.
    :param publisher_id: Publisher identifier.
    :param value: Increment value
    :return:
    """

    if publisher_id not in IMPRESSIONS_COUNT[banner_id]:
        IMPRESSIONS_COUNT[banner_id][publisher_id] = 0

    IMPRESSIONS_COUNT[banner_id][publisher_id] += value


def get_impression_count(banner_id, publisher_id):
    """

    :param banner_id: Banner identifier.
    :param publisher_id: Publisher identifier.
    :return: Impression count for banner for publisher.
    """
    return IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id, 0)


def get_impression_count_iter():
    """

    :return: An iterable of impression counts.
    """
    return IMPRESSIONS_COUNT.iteritems()


def delete_impression_count(banner_id):
    """
    Removes banner impression count.

    :param banner_id: Banner identifier.
    :return:
    """
    if banner_id in IMPRESSIONS_COUNT:
        del IMPRESSIONS_COUNT[banner_id]


#: Keep info about active banners
#:
#: BANNERS = {
#:     'size1': ['campaignid1_bannerid1',
#:                 'campaignid2_bannerid2'],
#:     'size2': ['campaignid2_bannerid2']
#: }
BANNERS = defaultdict(list)


def add_banner(banner_id, banner_size):
    """

    :param banner_id: Banner identifier.
    :param banner_size: Banner size.
    :return:
    """
    import logging

    logger = logging.getLogger(__name__)
    logger.debug((banner_id, banner_size))
    BANNERS[banner_size].append(banner_id)


def get_banners(size):
    """
    :param size:  Get all banners with this size.
    :return: List of banners or empty list.
    """
    return BANNERS.get(size, [])

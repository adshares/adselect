from collections import defaultdict
from adselect.contrib import utils as contrib_utils

#: Keep info about best paid keywords for the specific banner size. Keywords in the list are ordered from the best paid
#:
#: BEST_KEYWORDS: { 'publisher_id1':
#:    { 'size1':[keyword1, keyword2, ...], 'size2':[keyword1, keyword2, ...] }
#:    }
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

#: Keep info about active banners
#:
#: BANNERS = {
#:     'size1': ['campaignid1_bannerid1',
#:                 'campaignid2_bannerid2'],
#:     'size2': ['campaignid2_bannerid2']
#: }
BANNERS = defaultdict(list)

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


#: Keep data about total impressions count of banners
#:
#: IMPRESSIONS_COUNT = {
#:   'campaignid1_bannerid1': {
#:           'publisher_id1': 'impression_count_for_publisher_1',
#:           'publisher_id2': 'impression_count_for_publisher_2'
#:    },
#:  'campaignid2_bannerid2': { ... }
#: }
IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(lambda: int(0)))


def delete_impression_count(banner_id):
    """
    Removes banner impression count.

    :param banner_id: Banner identifier.
    :return:
    """
    if banner_id in IMPRESSIONS_COUNT:
        del IMPRESSIONS_COUNT[banner_id]

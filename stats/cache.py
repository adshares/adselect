import random, heapq, itertools

from adselect.contrib import utils as contrib_utils

# Keep info about best paid keywords for the specific banner size
# Kesywords in the list are ordered from the best paid
# BEST_KEYWORDS:{
#   'publisher_id1':{
#       'size1':[keyword1, keyword2, ....]
#       'size2':[keyword1, keyword2, ...]
#    }
# }
BEST_KEYWORDS = {}

# KEYWORDS_BANNERS keeps sorted list of banners for given size and keyword
# KEYWORDS_BANNERS = {
#   'publisher_id1':{
#      'size1':{
#          keyword1:[(avg_pay_amount, campaignid1_bannerid1), (avg_pay_amount, campaignid2_bannerid2), ...]
#          keyword2:[(avg_pay_amount, campaignid1_bannerid1), (avg_pay_amount, campaignid2_bannerid2), ..., ...]
#       },
#       'size2':{
#           keyword1:[(avg_pay_amount, campaignid1_bannerid1), (avg_pay_amount, campaignid2_bannerid2), ...]
#       }
#   },
#   'publisher_id2':{
#   }
# }
KEYWORDS_BANNERS = {}

# Keep info about banners impression payments
# KEYWORD_IMPRESSION_PAID_AMOUNT = {
#   'campaignid2_bannerid2':{
#       'publisher_id_1':{
#           'keyword1':'total_payment_amount',
#           'keyword2':'total_payment_amount',
#       },
#       'publisher_id_2':{
#           'keyword1':'total_payment_amount',
#           'keyword2':'total_payment_amount',
#       }
#   }
#  }
KEYWORD_IMPRESSION_PAID_AMOUNT = {}

# Keep info about new banners to display
# NEW_BANNERS:{
#   'size1':['campaignid1_bannerid1', 'campaignid2_bannerid2'],
#   'size2':['campaignid3_bannerid3', 'campaignid1_bannerid1']
# }
NEW_BANNERS = {}

# Keep data about impressions count of banners
# BANNERS_IMPRESSIONS_COUNT = {
#   'campaignid1_bannerid1':{
#           'publisher_id1':'impression_count_for_publisher_1',
#           'publisher_id2':'impression_count_for_publisher_2'
#    },
#   'campaignid2_bannerid2':{
#   }
# }
BANNERS_IMPRESSIONS_COUNT = {}


def select_new_banners(publisher_id,
                       banner_size,
                       proposition_nb,
                       notpaid_display_cutoff=100,
                       filtering_population_factor=4
                       ):
    """
        Return banners ids without payment statistic.
        The function doesn't allow to display banners more than notpaid_display_cutoff times without payment.
        publisher_id - publisher id
    """

    #TODO: fix shuffle of whole list
    new_banners = NEW_BANNERS.get(banner_size, [])
    random.shuffle(new_banners)
    random_banners = new_banners[:proposition_nb*filtering_population_factor]

    # Filter selected banners out banners witch were displayed more times than notpaid_display_cutoff
    selected_banners = []
    for banner_id in random_banners:
        if BANNERS_IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id, 0) < notpaid_display_cutoff:
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

    #selected best paid impression keywords
    publisher_best_keys = BEST_KEYWORDS.get(publisher_id, {}).get(banner_size, [])[:best_keywords_cutoff]
    sbpik = set(impression_keywords_dict.keys())&set(publisher_best_keys)

    #Select best paid banners with appropriate size

    selected_banners = []
    selected_banners_count = 0

    publisher_banners = KEYWORDS_BANNERS.get(publisher_id, {}).get(banner_size, {})
    for avg_price, banner_id in contrib_utils.merge(
            *[publisher_banners.get(keyword, [])[:banners_per_keyword_cutoff] for keyword in sbpik]
    ):

        selected_banners.append(banner_id)
        selected_banners_count +=1

        if selected_banners_count >= propositions_nb:
            break

    # Add new banners without payment statistic
    new_banners_proposition_nb = int(mixed_new_banners_percent*propositions_nb/100.0)
    selected_banners += select_new_banners(publisher_id, banner_size, new_banners_proposition_nb)
    random.shuffle(selected_banners)

    #Shuffle items in the list
    return selected_banners[:propositions_nb]


def update_impression(banner_id, banner_size, publisher_id, impression_keywords, paid_amount):
    # Update KEYWORD_IMPRESSION_PAID_AMOUNT and BANNERS_IMPRESSIONS_COUNT

    #TODO: add lock per impression?
    if banner_id not in BANNERS_IMPRESSIONS_COUNT:
        BANNERS_IMPRESSIONS_COUNT[banner_id] = {}

    if publisher_id not in BANNERS_IMPRESSIONS_COUNT[banner_id]:
        BANNERS_IMPRESSIONS_COUNT[banner_id][publisher_id]=0

    BANNERS_IMPRESSIONS_COUNT[banner_id][publisher_id]+=1


def add_new_banner(banner_id, banner_size):
    # Update NEW_BANNERS
    if banner_size not in NEW_BANNERS:
        NEW_BANNERS[banner_size] = []

    NEW_BANNERS[banner_size].append(banner_id)



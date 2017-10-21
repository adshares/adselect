from adselect.contrib import utils as contrib_utils

#########################################
########### BEST_KEYWORDS ###############
#########################################

# Keep info about best paid keywords for the specific banner size
# Kesywords in the list are ordered from the best paid
# BEST_KEYWORDS:{
#   'publisher_id1':{
#       'size1':[keyword1, keyword2, ....]
#       'size2':[keyword1, keyword2, ...]
#    }
# }
BEST_KEYWORDS = {}


def set_best_keywords(publisher_id, banner_size, keywords_list):
    if publisher_id not in BEST_KEYWORDS:
        BEST_KEYWORDS[publisher_id] = {}

    BEST_KEYWORDS[publisher_id][banner_size] = keywords_list


def get_best_keywords(publisher_id, banner_size):
    return BEST_KEYWORDS.get(publisher_id, {}).get(banner_size, [])


def delete_best_keywords():
    global BEST_KEYWORDS
    BEST_KEYWORDS = {}


# KEYWORDS_BANNERS keeps sorted list of banners for given size and keyword
# KEYWORDS_BANNERS = {
#   'publisher_id1':{
#      'size1':{
#          keyword1:[(pay_score, campaignid1_bannerid1), (pay_score, campaignid2_bannerid2), ...]
#          keyword2:[(pay_score, campaignid1_bannerid1), (pay_score, campaignid2_bannerid2), ..., ...]
#       },
#       'size2':{
#           keyword1:[(pay_score, campaignid1_bannerid1), (pay_score, campaignid2_bannerid2), ...]
#       }
#   },
#   'publisher_id2':{
#   }
# }
KEYWORDS_BANNERS = {}


def add_keyword_banner(publisher_id, banner_size, keyword, keyword_score, banner_id, limit=100):
    if publisher_id not in KEYWORDS_BANNERS:
        KEYWORDS_BANNERS[publisher_id] = {}

    if banner_size not in KEYWORDS_BANNERS[publisher_id]:
        KEYWORDS_BANNERS[publisher_id][banner_size] = {}

    if keyword not in KEYWORDS_BANNERS[publisher_id][banner_size]:
        KEYWORDS_BANNERS[publisher_id][banner_size][keyword] = []

    contrib_utils.reverse_insort(KEYWORDS_BANNERS[publisher_id][banner_size][keyword], (keyword_score, banner_id))
    KEYWORDS_BANNERS[publisher_id][banner_size][keyword] = KEYWORDS_BANNERS[publisher_id][banner_size][keyword][:limit]


def get_keyword_banners(publisher_id, banner_size):
    return KEYWORDS_BANNERS.get(publisher_id, {}).get(banner_size, [])


def delete_keyword_banners():
    global KEYWORDS_BANNERS
    KEYWORDS_BANNERS = {}


#########################################
### KEYWORD_IMPRESSION_PAID_AMOUNT ######
#########################################

# Keep info about last round impression payments > 0
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


def set_keyword_impression_paid_amount(banner_id, stats):
    KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id] = stats


def inc_keyword_impression_paid_amount(banner_id, publisher_id, keyword, value):
    if banner_id not in KEYWORD_IMPRESSION_PAID_AMOUNT:
        KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id] = {}

    if publisher_id not in KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]:
        KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id] = {}

    if keyword not in KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id]:
        KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][keyword] = 0

    KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][keyword] += value


def get_keyword_impression_paid_amount_iter():
    return KEYWORD_IMPRESSION_PAID_AMOUNT.iteritems()


def get_keyword_impression_paid_amount(banner_id, publisher_id, keyword):
    return KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).get(publisher_id, {}).get(keyword, 0)


def get_last_round_paid_banners():
    return KEYWORD_IMPRESSION_PAID_AMOUNT.keys()


def get_last_round_paid_banner_publishers(banner_id):
    return KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).keys()


def get_last_round_paid_banner_publisher_keywords(banner_id, publisher_id):
    return KEYWORD_IMPRESSION_PAID_AMOUNT.get(banner_id, {}).get(publisher_id, {}).keys()


#########################################
######### IMPRESSIONS_COUNT #############
#########################################

# Keep data about total impressions count of banners
# IMPRESSIONS_COUNT = {
#   'campaignid1_bannerid1':{
#           'publisher_id1':'impression_count_for_publisher_1',
#           'publisher_id2':'impression_count_for_publisher_2'
#    },
#   'campaignid2_bannerid2':{
#   }
# }
IMPRESSIONS_COUNT = {}


def set_impression_count(banner_id, publisher_id, value):
    if banner_id not in IMPRESSIONS_COUNT:
        IMPRESSIONS_COUNT[banner_id] = {}
    IMPRESSIONS_COUNT[banner_id][publisher_id] = value


def inc_impression_count(banner_id, publisher_id, value=1):
    if banner_id not in IMPRESSIONS_COUNT:
        IMPRESSIONS_COUNT[banner_id] = {}

    if publisher_id not in IMPRESSIONS_COUNT[banner_id]:
        IMPRESSIONS_COUNT[banner_id][publisher_id] = 0

    IMPRESSIONS_COUNT[banner_id][publisher_id] += value


def get_impression_count(banner_id, publisher_id):
    return IMPRESSIONS_COUNT.get(banner_id, {}).get(publisher_id, 0)


def get_impression_count_iter():
    return IMPRESSIONS_COUNT.iteritems()


def delete_impression_count(banner_id):
    if banner_id in IMPRESSIONS_COUNT:
        del IMPRESSIONS_COUNT[banner_id]


#########################################
######### BANERS ########################
#########################################

# Keep info about active banners
# BANNERS = {
#     'size1':['campaignid1_bannerid1', 'campaignid2_bannerid2', ],
#     'size2':['campaignid2_bannerid2']
# }
BANNERS = {}


def add_banner(banner_id, banner_size):
    if banner_size not in BANNERS:
        BANNERS[banner_size] = []

    BANNERS[banner_size].append(banner_id)


def get_banners(size):
    return BANNERS.get(size, [])

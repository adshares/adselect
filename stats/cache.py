# Keep info about best paid keywords for the specific banner size
# Kesywords in the list are ordered from the best paid
# BEST_KEYWORDS:{
#   'publisher_id1':{
#       'size1':[keyword1, keyword2, ....]
#       'size2':[keyword1, keyword2, ...]
#    }
# }
BEST_KEYWORDS = {}

def set_best_keywords(best_keywords_dict):
    global BEST_KEYWORDS
    BEST_KEYWORDS = best_keywords_dict


# KEYWORDS_BANNERS keeps sorted list of banners for given size and keyword
# KEYWORDS_BANNERS = {
#   'publisher_id1':{
#      'size1':{
#          keyword1:[(pay_score, campaignid1_bannerid1), (pay_score, campaignid2_bannerid2), ...]
#          keyword2:[(pay_score, campaignid1_bannerid1), (pay_score, campaignid2_bannerid2), ..., ...]
#       },
#       'size2':{
#           keyword1:[(avg_pay_amount, campaignid1_bannerid1), (avg_pay_amount, campaignid2_bannerid2), ...]
#       }
#   },
#   'publisher_id2':{
#   }
# }
KEYWORDS_BANNERS = {}

def set_keywords_banners(keywords_banners):
    global KEYWORDS_BANNERS
    KEYWORDS_BANNERS = keywords_banners

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
        IMPRESSIONS_COUNT[banner_id][publisher_id]=0

    IMPRESSIONS_COUNT[banner_id][publisher_id]+=value


def delete_impression_count(banner_id):
    if banner_id in IMPRESSIONS_COUNT:
        del IMPRESSIONS_COUNT[banner_id]


# Keep info about active banners
# BANNERS = {
#     'size1':['campaignid1_bannerid1', 'campaignid2_bannerid2', ],
#     'size2':['campaignid2_bannerid2']
# }
BANNERS = {}

def add_banner(banner_id, banner_size):
    if not banner_size in BANNERS:
        BANNERS[banner_size] = []

    BANNERS[banner_size].append(banner_id)


def get_banners(size):
    return BANNERS.get(size, [])



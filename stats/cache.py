# Keep info about best paid keywords for the specific banner size
# Kesywords in the list are ordered from the best paid
# BEST_KEYWORDS:{
#   'publisher_id1':{
#       'size1':[keyword1, keyword2, ....]
#       'size2':[keyword1, keyword2, ...]
#    }
# }
BEST_KEYWORDS = {}

def update_best_keywords(best_keywords_dict):
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

def update_keywords_banners(keywords_banners):
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

def update_keyword_impression_paid_amount(banner_id, stats):
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

def update_banners(banners):
    global BANNERS
    BANNERS = banners


def genkey(key, val, delimiter="_"):
    keywal = "%s%s%s" % (key, delimiter, val)
    return keywal.replace(".", "")


def update_impression(banner_id, publisher_id, impression_keywords, paid_amount):
    # Update BANNERS_IMPRESSIONS_COUNT
    inc_impression_count(banner_id, publisher_id, 1)

    # Update KEYWORD_IMPRESSION_PAID_AMOUNT if paid_amount > 0
    if not paid_amount > 0:
        return

    if banner_id not in KEYWORD_IMPRESSION_PAID_AMOUNT:
        KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id] = {}

    if publisher_id not in KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]:
        KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id] = {}

    for key, val in impression_keywords.items():
        stat_key = genkey(key, val)
        if stat_key not in KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id]:
            KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][stat_key] = 0

        KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][stat_key]+=paid_amount
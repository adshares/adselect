from adselect import db

########################
#### CAMPAIGNS #########
########################
def get_campaign(campaign_id):
    return db.get_campaign_collection().find_one({'campaign_id':campaign_id})


def update_campaign(campaign_doc):
    return db.get_campaign_collection().replace_one({'campaign_id':campaign_doc['campaign_id']},
                                                    campaign_doc, upsert=True)

def delete_campaigns(campaign_id):
    return db.get_campaign_collection().delete_many({'campaign_id':campaign_id})

#########################
##### BANNERS ###########
#########################

def get_banners_iter():
    return db.get_banner_collection().find(cursor=True)


def get_banner(banner_id):
    return db.get_banner_collection().find_one({'banner_id':banner_id})


def update_banner(banner_doc):
    return db.get_banner_collection().replace_one({'banner_id':banner_doc['banner_id']},
                                                  banner_doc, upsert=True)

def delete_campaign_banners(campaign_id):
    return db.get_banner_collection().delete_many({'campaign_id':campaign_id})


############################
##### STATS ################
############################


def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    impression_stats_collection = db.get_impressions_stats_collection()
    return impression_stats_collection.replace_one({"banner_id":banner_id},
                                                   {"stats": counts_per_publisher_dict, "banner_id":banner_id},
                                                   upsert=True)


def get_banner_impression_count_iter():
    return db.get_impressions_stats_collection().find(cursor=True)


def update_banner_payment(banner_id, pay_per_publisher_per_keyword_dict):
    payments_stats_collections = db.get_payments_stats_collection()
    return payments_stats_collections.replace_one({"banner_id":banner_id},
                                                  {"stats": pay_per_publisher_per_keyword_dict, "banner_id":banner_id},
                                                  upsert=True)


def get_banner_payment_iter():
    return db.get_payments_stats_collection().find(cursor=True)
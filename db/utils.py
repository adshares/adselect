from adselect import db

def add_or_update_campaign(campaign_doc):
    return db.get_campaign_collection().insert(campaign_doc, safe=True)


def add_or_update_banner(banner_doc):
    return db.get_banner_collection().insert(banner_doc, safe=True)


def get_banners_iter():
    return db.get_banner_collection().find(cursor=True)


def get_banner(banner_id):
    return db.get_banner_collection().find_one({'banner_id':banner_id})


def delete_campaigns(campaigns_ids_list):
    """
        campaigns_ids_list list of id to delete
    """


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
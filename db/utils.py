from adselect import db
from twisted.internet import defer


# CAMPAIGNS #
@defer.inlineCallbacks
def get_campaign(campaign_id):
    collection = yield db.get_campaign_collection()
    return_value = yield collection.find_one({'campaign_id': campaign_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_campaigns_iter():
    collection = yield db.get_campaign_collection()
    return_value = yield collection.find(cursor=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_campaign(campaign_doc):
    collection = yield db.get_campaign_collection()
    return_value = yield collection.replace_one({'campaign_id': campaign_doc['campaign_id']},
                                                campaign_doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign(campaign_id):
    collection = yield db.get_campaign_collection()
    return_value = yield collection.delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)


# BANNERS #


@defer.inlineCallbacks
def get_banners_iter():
    banner_coll = yield db.get_banner_collection()
    return_value = yield banner_coll.find(cursor=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_banner(banner_id):
    banner_coll = yield db.get_banner_collection()
    return_value = yield banner_coll.find_one({'banner_id': banner_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_banner(banner_doc):
    banner_coll = yield db.get_banner_collection()
    return_value = yield banner_coll.replace_one({'banner_id': banner_doc['banner_id']},
                                                 banner_doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_campaign_banners(campaign_id):
    banner_coll = yield db.get_banner_collection()
    return_value = yield banner_coll.find({'campaign_id': campaign_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign_banners(campaign_id):
    banner_coll = yield db.get_banner_collection()
    return_value = yield banner_coll.delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)

# STATS #

# IMPRESSION COUNTS #


@defer.inlineCallbacks
def get_banner_impression_count(banner_id):
    collection = yield db.get_impressions_stats_collection()
    return_value = yield collection.find_one({'banner_id': banner_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_banner_impression_count_iter():
    collection = yield db.get_impressions_stats_collection()
    return_value = yield collection.find(cursor=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    impression_stats_collection = yield db.get_impressions_stats_collection()
    return_value = yield impression_stats_collection.replace_one({"banner_id": banner_id},
                                                                 {"banner_id": banner_id,
                                                                  "stats": counts_per_publisher_dict},
                                                                 upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_impression_count(banner_id):
    collection = yield db.get_impressions_stats_collection()
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# PAYMENTS #
@defer.inlineCallbacks
def get_banner_payment(banner_id):
    collection = yield db.get_impressions_stats_collection()
    return_value = yield collection.find_one({'banner_id': banner_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_banner_payment_iter():
    collection = yield db.get_payments_stats_collection()
    return_value = yield collection.find(cursor=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_banner_payment(banner_id, pay_per_publisher_per_size_per_keyword_dict):
    collection = yield db.get_payments_stats_collection()
    return_value = yield collection.replace_one({"banner_id": banner_id},
                                                {"banner_id": banner_id,
                                                 "stats": pay_per_publisher_per_size_per_keyword_dict},
                                                upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_payments(banner_id):
    collection = yield db.get_payments_stats_collection()
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# SCORES #
@defer.inlineCallbacks
def get_banner_scores(banner_id):
    collection = yield db.get_scores_stats_collection()
    return_value = yield collection.find_one({'banner_id': banner_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_banner_scores_iter():
    collection = yield db.get_scores_stats_collection()
    return_value = yield collection.find(cursor=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_banner_scores(banner_id, score_per_publisher_per_keyword_dict):
    collection = yield db.get_scores_stats_collection()
    return_value = yield collection.replace_one({'banner_id': banner_id},
                                                {'banner_id': banner_id,
                                                 'stats': score_per_publisher_per_keyword_dict},
                                                upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_scores(banner_id):
    collection = yield db.get_scores_stats_collection()
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)

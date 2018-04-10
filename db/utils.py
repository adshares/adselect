from adselect import db
from twisted.internet import defer

# ALL #


def get_collection_iter(collection_name):
    return db.get_collection(collection_name).find(cursor=True)


# CAMPAIGNS #
def get_campaign(campaign_id):
    return db.get_collection('campaign').find_one({'campaign_id': campaign_id})


@defer.inlineCallbacks
def update_campaign(campaign_doc):
    return_value = yield db.get_collection('campaign').replace_one({'campaign_id': campaign_doc['campaign_id']},
                                                                   campaign_doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign(campaign_id):
    return_value = yield db.get_collection('campaign').delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)


# BANNERS #

def get_banner(banner_id):
    return db.get_collection('banner').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner(banner_doc):
    return_value = yield db.get_collection('banner').replace_one({'banner_id': banner_doc['banner_id']},
                                                                 banner_doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_campaign_banners(campaign_id):
    banner_coll = yield db.get_collection('banner')
    return_value = yield banner_coll.find({'campaign_id': campaign_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign_banners(campaign_id):
    banner_coll = yield db.get_collection('banner')
    return_value = yield banner_coll.delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)

# STATS #

# IMPRESSION COUNTS #


def get_banner_impression_count(banner_id):
    return db.get_collection('impression_stats').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    return_value = yield db.get_collection('impression_stats').replace_one({"banner_id": banner_id},
                                                                            {"banner_id": banner_id,
                                                                             "stats": counts_per_publisher_dict},
                                                                             upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_impression_count(banner_id):
    collection = yield db.get_collection('impression_stats')
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# PAYMENTS #
def get_banner_payment(banner_id):
    return db.get_collection('impression_stats').find_one({'banner_id': banner_id})
  

def get_banner_payment_iter():
    return db.get_collection('payments_stats').find(cursor=True)


@defer.inlineCallbacks
def update_banner_payment(banner_id, pay_per_publisher_per_size_per_keyword_dict):
    collection = yield db.get_collection('payments_stats')
    return_value = yield collection.replace_one({"banner_id": banner_id},
                                                {"banner_id": banner_id,
                                                 "stats": pay_per_publisher_per_size_per_keyword_dict},
                                                upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_payments(banner_id):
    collection = yield db.get_collection('payments_stats')
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# SCORES #
def get_banner_scores(banner_id):
    return db.get_collection('scores_stats').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner_scores(banner_id, score_per_publisher_per_keyword_dict):
    collection = yield db.get_collection('scores_stats')
    return_value = yield collection.replace_one({'banner_id': banner_id},
                                                {'banner_id': banner_id,
                                                 'stats': score_per_publisher_per_keyword_dict},
                                                upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_scores(banner_id):
    collection = yield db.get_collection('scores_stats')
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)

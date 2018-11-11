from twisted.internet import defer

from adselect import db


# ALL #


def get_collection_iter(collection_name):
    """
    Returns a batch of documents iterable and a deferred. The deferred can be called to get another batch.

    :param collection_name: Name of the collection we iterate over.
    """
    return db.get_collection(collection_name).find(cursor=True)


# CAMPAIGNS #
def get_campaign(campaign_id):
    """
    :param campaign_id: campaign_id
    :return: One campaign object with the corresponding id.
    """
    return db.get_collection('campaign').find_one({'campaign_id': campaign_id})


@defer.inlineCallbacks
def update_campaign(campaign_doc):
    """
    Update campaign data or create one if doesn't exist.

    :param campaign_doc: New campaign data, must include campaign_id to identify existing data.
    :return: deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('campaign').replace_one({'campaign_id': campaign_doc['campaign_id']},
                                                                   campaign_doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign(campaign_id):
    """
    Remove campaign data. Doesn't remove banners or other associated data.

    :param campaign_id: id of the campaign
    :return: deferred
    """
    return_value = yield db.get_collection('campaign').delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)


# BANNERS #

def get_banner(banner_id):
    """
    :param banner_id: id of the banner
    :return: One banner with the corresponding id.
    """
    return db.get_collection('banner').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner(banner_doc):
    """
    Update banner data or create a new one if doesn't exist.

    :param banner_doc: New banner data, must include banner_id.
    :return: deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('banner').replace_one({'banner_id': banner_doc['banner_id']},
                                                                 banner_doc, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_campaign_banners(campaign_id):
    """
    :param campaign_id: campaign_id for the banners
    :return: deferred banner iterable
    """
    banner_coll = yield db.get_collection('banner')
    return_value = yield banner_coll.find({'campaign_id': campaign_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign_banners(campaign_id):
    """
    Remove banners.

    :param campaign_id: campaign_id, to which the banners belong to
    :return: deferred
    """
    banner_coll = yield db.get_collection('banner')
    return_value = yield banner_coll.delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)

# STATS #

# IMPRESSION COUNTS #


def get_banner_impression_count(banner_id):
    """
    :param banner_id: banner identification
    :return: Impression count object for banner.
    """
    return db.get_collection('impressions_stats').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    """
    Updates the banner impression count

    :param banner_id: banner identification
    :param counts_per_publisher_dict: Dictionary for
    :return: deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    return_value = yield db.get_collection('impressions_stats').replace_one({"banner_id": banner_id},
                                                                            {"banner_id": banner_id,
                                                                             "stats": counts_per_publisher_dict},
                                                                            upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_impression_count(banner_id):
    """
    Removes all count information about banner impressions.

    :param banner_id: Banner identification
    :return:
    """
    collection = yield db.get_collection('impressions_stats')
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# PAYMENTS #
def get_banner_payment(banner_id):
    """

    :param banner_id: Banner identifier.
    :return: Payment information for the banner.
    """
    return db.get_collection('payments_stats').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner_payment(banner_id, pay_per_publisher_per_size_per_keyword_dict):
    """

    :param banner_id: Banner identifier.
    :param pay_per_publisher_per_size_per_keyword_dict:
    :return: deferred instance of :class:`pymongo.results.UpdateResult`.
    """
    collection = yield db.get_collection('payments_stats')
    return_value = yield collection.replace_one({"banner_id": banner_id},
                                                {"banner_id": banner_id,
                                                 "stats": pay_per_publisher_per_size_per_keyword_dict},
                                                upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_payments(banner_id):
    """
    Removes banner payment information.

    :param banner_id: Banner identifier.
    :return:
    """
    collection = yield db.get_collection('payments_stats')
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# SCORES #
def get_banner_scores(banner_id):
    """

    :param banner_id: Banner identifier.
    :return:
    """
    return db.get_collection('scores_stats').find_one({'banner_id': banner_id})


@defer.inlineCallbacks
def update_banner_scores(banner_id, score_per_publisher_per_keyword_dict):
    """

    :param banner_id: Banner identifier.
    :param score_per_publisher_per_keyword_dict:
    :return:
    """
    collection = yield db.get_collection('scores_stats')
    return_value = yield collection.replace_one({'banner_id': banner_id},
                                                {'banner_id': banner_id,
                                                 'stats': score_per_publisher_per_keyword_dict},
                                                upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_scores(banner_id):
    """
    Removes all banner scores for a given banner.

    :param banner_id: Banner identifier.
    :return:
    """
    collection = yield db.get_collection('scores_stats')
    return_value = yield collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)

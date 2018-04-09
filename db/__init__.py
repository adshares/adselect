from adselect.db import const as db_consts
from twisted.internet import defer
from txmongo import filter
import txmongo


@defer.inlineCallbacks
def configure_db():
    yield get_mongo_db()

    # Creating indexes when daemon starts
    campaign_idx = filter.sort(filter.ASCENDING("campaign_id"))
    banner_idx = filter.sort(filter.ASCENDING("banner_id"))

    # Campaign collection
    campaign_collection = yield get_campaign_collection()
    yield campaign_collection.create_index(campaign_idx, unique=True)

    # Banner collection
    banner_collection = yield get_banner_collection()
    yield banner_collection.create_index(banner_idx, unique=True)
    yield banner_collection.create_index(campaign_idx)

    # Stats collection
    impressions_stats_collection = yield get_impressions_stats_collection()
    yield impressions_stats_collection.create_index(banner_idx, unique=True)

    payments_stats_collection = yield get_payments_stats_collection()
    yield payments_stats_collection.create_index(banner_idx, unique=True)

    scores_stats_collection = yield get_scores_stats_collection()
    yield scores_stats_collection.create_index(banner_idx, unique=True)


@defer.inlineCallbacks
def get_mongo_db():
    conn = yield get_mongo_connection()
    defer.returnValue(conn.spotree)


@defer.inlineCallbacks
def get_campaign_collection():
    # Keep information about campaigns
    db = yield get_mongo_db()
    defer.returnValue(db.campaign)


@defer.inlineCallbacks
def get_banner_collection():
    # Keep information about banners
    db = yield get_mongo_db()
    defer.returnValue(db.banners)


@defer.inlineCallbacks
def get_payments_stats_collection():
    db = yield get_mongo_db()
    defer.returnValue(db.pay_stats)


@defer.inlineCallbacks
def get_impressions_stats_collection():
    db = yield get_mongo_db()
    defer.returnValue(db.imp_stats)


@defer.inlineCallbacks
def get_scores_stats_collection():
    db = yield get_mongo_db()
    defer.returnValue(db.score_status)


MONGO_CONNECTION = None


@defer.inlineCallbacks
def get_mongo_connection():
    global MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = yield txmongo.lazyMongoConnectionPool(port=db_consts.MONGO_DB_PORT)
    defer.returnValue(MONGO_CONNECTION)


@defer.inlineCallbacks
def disconnect():
    global MONGO_CONNECTION
    conn = yield get_mongo_connection()
    yield conn.disconnect()
    MONGO_CONNECTION = None

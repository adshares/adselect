from adselect.db import const as db_consts

from txmongo import filter
import txmongo


def configure_db():
    get_mongo_db()

    # Creating indexes when daemon starts
    campaign_idx = filter.sort(filter.ASCENDING("campaign_id"))
    banner_idx = filter.sort(filter.ASCENDING("banner_id"))

    # Campaign collection
    get_campaign_collection().create_index(campaign_idx, unique=True)

    # Banner collection
    get_banner_collection().create_index(banner_idx, unique=True)
    get_banner_collection().create_index(campaign_idx)

    # Stats collection
    get_impressions_stats_collection().create_index(banner_idx, unique=True)
    get_payments_stats_collection().create_index(banner_idx, unique=True)
    get_scores_stats_collection().create_index(banner_idx, unique=True)


def get_mongo_db():
    return get_mongo_connection().spotree


def get_campaign_collection():
    # Keep information about campaigns
    return get_mongo_db().campaign


def get_banner_collection():
    # Keep information about banners
    return get_mongo_db().banners


def get_payments_stats_collection():
    return get_mongo_db().pay_stats


def get_impressions_stats_collection():
    return get_mongo_db().imp_stats


def get_scores_stats_collection():
    return get_mongo_db().score_stats


MONGO_CONNECTION = None


def get_mongo_connection():
    global MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool(port=db_consts.MONGO_DB_PORT)
    return MONGO_CONNECTION


def disconnect():
    get_mongo_connection().disconnect()

import txmongo


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


MONGO_CONNECTION = None
def get_mongo_db():
    from adselect.db import MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool().spotree
    return MONGO_CONNECTION


def configure_db():
    get_mongo_db()
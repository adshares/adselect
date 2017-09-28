import txmongo


def get_stat_collection():
    return get_mongo_db().stats


def get_campaign_collection():
    return get_mongo_db().campaign


MONGO_CONNECTION = None
def get_mongo_db():
    from adselect.db import MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool()
    return MONGO_CONNECTION


def configure_db():
    get_mongo_db()
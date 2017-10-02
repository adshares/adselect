import txmongo


def configure_db():
    get_mongo_db()


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
        print "Starting lazy connection"
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool()
    return MONGO_CONNECTION


def disconnect():
    get_mongo_connection().disconnect()

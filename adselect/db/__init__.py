from adselect.db import const as db_consts
from twisted.internet import defer
from txmongo import filter
import txmongo


@defer.inlineCallbacks
def configure_db():
    """
    Configures the database
    """
    yield get_mongo_db()

    # Creating indexes when daemon starts
    campaign_idx = filter.sort(filter.ASCENDING("campaign_id"))
    banner_idx = filter.sort(filter.ASCENDING("banner_id"))

    # Campaign collection
    yield get_collection('campaign').create_index(campaign_idx, unique=True)

    # Banner collection
    banner_collection = yield get_collection('banner')
    yield banner_collection.create_index(banner_idx, unique=True)
    yield banner_collection.create_index(campaign_idx)

    # Stats collection
    yield get_collection('impressions_stats').create_index(banner_idx, unique=True)
    yield get_collection('payment_stats').create_index(banner_idx, unique=True)
    yield get_collection('scores_stats').create_index(banner_idx, unique=True)


def get_mongo_db():
    """

    :return: MongoDB instance
    """
    conn = get_mongo_connection()
    return conn.adselect


def get_collection(name):
    """

    :param name: Name of MongoDB collection
    :return: deferred instance of :class:`txmongo.collection.Collection`.
    """
    db = get_mongo_db()
    return getattr(db, name)


#: Global MongoDB connection.
MONGO_CONNECTION = None


def get_mongo_connection():
    """

    :return: Global connection to MongoDB
    """
    global MONGO_CONNECTION
    if MONGO_CONNECTION is None:
        MONGO_CONNECTION = txmongo.lazyMongoConnectionPool(port=db_consts.MONGO_DB_PORT)
    return MONGO_CONNECTION


@defer.inlineCallbacks
def disconnect():
    """
    Disconnects asynchronously and removes global connection.
    """
    global MONGO_CONNECTION
    if MONGO_CONNECTION:
        conn = yield get_mongo_connection()
        yield conn.disconnect()
        MONGO_CONNECTION = None

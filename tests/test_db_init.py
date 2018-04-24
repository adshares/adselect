import re
import time

from mock import patch, Mock, MagicMock, PropertyMock

from twisted.trial import unittest
from twisted.internet import defer
import txmongo

import adselect
from adselect import db


class DbInitTestCase(unittest.TestCase):

    def setUp(self):

        adselect.db.MONGO_CONNECTION = None

        self.banner_collection = MagicMock()

        self.mock_database = MagicMock()
        self.mock_database.banner = self.banner_collection

        self.mock_connection = MagicMock()
        self.mock_connection.adselect = self.mock_database
        self.mock_connection.disconnect.return_value = True

        self.mock_lazyMongoConnectionPool = MagicMock()
        self.mock_lazyMongoConnectionPool.return_value = self.mock_connection

        self.original_lazyMongoConnectionPool = txmongo.lazyMongoConnectionPool
        self.patch(txmongo, 'lazyMongoConnectionPool', self.mock_lazyMongoConnectionPool)

    def tearDown(self):
        txmongo.lazyMongoConnectionPool = self.original_lazyMongoConnectionPool

    def test_get_mongo_db(self):

        database = adselect.db.get_mongo_db()
        self.assertIs(database, self.mock_database)

    def test_get_mongo_connection(self):

        self.assertIsNone(adselect.db.MONGO_CONNECTION)

        connection = db.get_mongo_connection()

        self.assertEqual(connection, self.mock_lazyMongoConnectionPool.return_value)
        self.assertIs(connection, adselect.db.MONGO_CONNECTION)

    def test_get_collection(self):

        coll = db.get_collection('banner')
        self.assertIsNotNone(coll)

    @defer.inlineCallbacks
    def test_configure_db(self):
        yield db.configure_db()

    @defer.inlineCallbacks
    def test_disconnect(self):

        self.assertIsNone(adselect.db.MONGO_CONNECTION)

        disconnected = yield db.disconnect()
        self.assertIsNone(disconnected)

        connection = db.get_mongo_connection()
        self.assertIsNotNone(connection)
        self.assertIsNotNone(adselect.db.MONGO_CONNECTION)

        disconnected = yield db.disconnect()
        self.assertIsNone(disconnected)


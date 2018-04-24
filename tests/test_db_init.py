from twisted.internet import defer

from adselect import db

from tests import MockDBTestCase


class DbInitTestCase(MockDBTestCase):

    def test_get_mongo_db(self):

        database = db.get_mongo_db()
        self.assertIs(database, self.mock_database)

    def test_get_mongo_connection(self):

        self.assertIsNone(db.MONGO_CONNECTION)

        connection = db.get_mongo_connection()

        self.assertEqual(connection, self.mock_lazyMongoConnectionPool.return_value)
        self.assertIs(connection, db.MONGO_CONNECTION)

    def test_get_collection(self):

        coll = db.get_collection('banner')
        self.assertIsNotNone(coll)

    @defer.inlineCallbacks
    def test_configure_db(self):
        yield db.configure_db()

    @defer.inlineCallbacks
    def test_disconnect(self):

        self.assertIsNone(db.MONGO_CONNECTION)

        disconnected = yield db.disconnect()
        self.assertIsNone(disconnected)

        connection = db.get_mongo_connection()
        self.assertIsNotNone(connection)
        self.assertIsNotNone(db.MONGO_CONNECTION)

        disconnected = yield db.disconnect()
        self.assertIsNone(disconnected)


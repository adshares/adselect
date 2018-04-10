from twisted.trial import unittest
from twisted.internet import defer

from adselect import db
import uuid
import random


class StatsTestCase(unittest.TestCase):
    @defer.inlineCallbacks
    def setUp(self):
        self.conn = yield db.get_mongo_connection()
        self.db = yield db.get_mongo_db()

        yield db.configure_db()
        self.timeout = 5

    @defer.inlineCallbacks
    def tearDown(self):
        yield self.conn.drop_database(self.db)
        yield db.disconnect()

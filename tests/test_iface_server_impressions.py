from copy import deepcopy

from fastjsonrpc.jsonrpc import JSONRPCError
from twisted.internet import defer

from adselect.iface import server as iface_server
from tests import db_test_case


class TestAdSelectIfaceServer(db_test_case):

    def setUp(self):
        self.server = iface_server.AdSelectIfaceServer()
        self.campaigns = deepcopy(self._campaigns)
        self.impressions = deepcopy(self._impressions)

    @defer.inlineCallbacks
    def test_jsonrpc_impression_add(self):
        ret = yield self.server.jsonrpc_impression_add()
        self.assertTrue(ret)

        ret = yield self.server.jsonrpc_impression_add(*self.impressions)
        self.assertTrue(ret)

        with self.assertRaises(JSONRPCError):

            request = {'wrong_key': 'wrong_value'}
            yield self.server.jsonrpc_impression_add(request)

    @defer.inlineCallbacks
    def test_jsonrpc_impression_payment_add(self):
        ret = yield self.server.jsonrpc_impression_payment_add()
        self.assertTrue(ret)

        impression_payment = {"banner_id": "banner_id",
                              "user_id": "user_id",
                              "event_id": "event_id",
                              "publisher_id": "publisher_id"}

        ret = yield self.server.jsonrpc_impression_payment_add(impression_payment)
        self.assertTrue(ret)

        with self.assertRaises(JSONRPCError):

            request = {'wrong_key': 'wrong_value'}
            yield self.server.jsonrpc_impression_payment_add(request)

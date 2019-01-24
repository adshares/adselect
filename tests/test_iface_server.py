from copy import deepcopy
from unittest import TestCase

from twisted.internet import defer

from adselect.iface import server as iface_server
from tests import db_test_case


class TestAdSelectIfaceServer(db_test_case):

    def setUp(self):
        self.server = iface_server.AdSelectIfaceServer()
        self.campaigns = deepcopy(self._campaigns)
        self.impressions = deepcopy(self._impressions)

    @defer.inlineCallbacks
    def test_jsonrpc_campaign_update(self):
        ret = yield self.server.jsonrpc_campaign_update()
        self.assertTrue(ret)

        ret = yield self.server.jsonrpc_campaign_update(*self.campaigns)
        self.assertTrue(ret)

    @defer.inlineCallbacks
    def test_jsonrpc_campaign_delete(self):
        ret = yield self.server.jsonrpc_campaign_delete()
        self.assertTrue(ret)

        ret = yield self.server.jsonrpc_campaign_update(*self.campaigns)
        self.assertTrue(ret)

        ret = yield self.server.jsonrpc_campaign_delete(*[cid['campaign_id'] for cid in self.campaigns])
        self.assertTrue(ret)

    @defer.inlineCallbacks
    def test_jsonrpc_impression_add(self):
        ret = yield self.server.jsonrpc_impression_add()
        self.assertTrue(ret)

        ret = yield self.server.jsonrpc_impression_add(*self.impressions)
        self.assertTrue(ret)

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

    @defer.inlineCallbacks
    def test_jsonrpc_banner_select(self):
        ret = yield self.server.jsonrpc_banner_select()
        self.assertEqual(len(ret), 0)

        yield self.server.jsonrpc_campaign_update(*self.campaigns)
        yield self.server.jsonrpc_impression_add(*self.impressions)

        ret = yield self.server.jsonrpc_banner_select()
        self.assertEqual(len(ret), 0)

        request = {
                   'request_id': 0,
                   'publisher_id': 'publisher_id',
                   'user_id': 'user_id',
                   'banner_size': '16x16',
                   'keywords': {},
                   'banner_filters': {'require': {},
                                      'exclude': {}}
                   }

        ret = yield self.server.jsonrpc_banner_select(request)
        self.assertEqual(len(ret), 0)


class TestConfigureIfaceServer(TestCase):

    def test_configure_iface(self):
        self.reactor = iface_server.configure_iface(port=9090)
        self.assertIsNotNone(self.reactor)
        self.reactor.stopListening()

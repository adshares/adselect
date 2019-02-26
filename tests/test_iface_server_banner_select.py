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
        self.assertEqual(len(ret), 1)

        with self.assertRaises(JSONRPCError):
            request = {'wrong_key': 'wrong_value'}
            yield self.server.jsonrpc_banner_select(request)

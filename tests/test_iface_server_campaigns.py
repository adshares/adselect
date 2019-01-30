from copy import deepcopy

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

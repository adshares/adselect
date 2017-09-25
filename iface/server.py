from twisted.internet import reactor
from twisted.web.server import Site

from fastjsonrpc.server import JSONRPCServer

from adselect.iface import config as iface_config
from adselect.db import utils as db_utils
from adselect.selector import utils as selector_utils


class AdSelectIfaceServer(JSONRPCServer):
    #campaign interface
    def jsonrpc_campaign_update(self, *campaign_data):
        db_utils.update_campaigns(campaign_data)
        return True

    def jsonrpc_campaign_delete(self, *campaign_id):
        db_utils.delete_campaigns(campaign_id)
        return True

    #impressions interface
    def jsonrpc_impression_add(self, *impressions_data):
        db_utils.add_impressions(impressions_data)
        return True

    #select banner interface
    def jsonrpc_banner_select(self, *impression_param):
        return selector_utils.select_banner(impression_param)


def configure_iface(port = iface_config.SERVER_PORT):
    site = Site(AdSelectIfaceServer())
    reactor.listenTCP(port, site)

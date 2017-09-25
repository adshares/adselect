from twisted.internet import reactor
from twisted.web.server import Site

from fastjsonrpc.server import JSONRPCServer

from adselect.iface import config as iface_config
from adselect.db import utils as db_utils
from adselect.selector import utils as selector_utils
from adselect.iface import models as iface_models


class AdSelectIfaceServer(JSONRPCServer):
    #campaign interface
    def jsonrpc_campaign_update(self, *campaign_data_list):
        db_utils.update_campaigns(campaign_data_list)
        return True

    def jsonrpc_campaign_delete(self, *campaign_id_list):
        db_utils.delete_campaigns(campaign_id_list)
        return True

    #impressions interface
    def jsonrpc_impression_add(self, *impressions_data_list):
        db_utils.add_impressions(impressions_data_list)
        return True

    #select banner interface
    def jsonrpc_banner_select(self, *impression_param_list):
        banner_requests = [iface_models.SelectBannerRequest(impression_param)
                          for impression_param in impression_param_list]

        return [response.to_json() for response in selector_utils.select_banner(banner_requests)]


def configure_iface(port = iface_config.SERVER_PORT):
    site = Site(AdSelectIfaceServer())
    reactor.listenTCP(port, site)

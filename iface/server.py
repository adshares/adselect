from twisted.internet import reactor
from txjason.netstring import JSONRPCServerFactory
from txjason import handler

from adselect.iface import config as iface_config
from adselect.db import utils as db_utils
from adselect.selector import utils as selector_utils


class AdSelectHandler(handler.Handler):
    pass


class CampaignUpdateHandler(AdSelectHandler):
    """
        Update all info about campaign.
    """

    @handler.exportRPC()
    def update(self, campaign_data):
        if type(campaign_data)!= list:
            campaign_data = [campaign_data]
        db_utils.update_campaigns(campaign_data)
        return "OK"

    @handler.exportRPC()
    def delete(self, campaign_id):
        if type(campaign_id)!=list:
            campaign_id = [campaign_id]
        db_utils.delete_campaigns(campaign_id)
        return "OK"


class ImpressionsUpdateHandler(AdSelectHandler):
    """
        Update all infos about impressions.
    """

    @handler.exportRPC()
    def add(self, impressions_data_list):
        if type(impressions_data_list)!=list:
            impressions_data_list = [impressions_data_list]
        db_utils.add_impressions(impressions_data_list)
        return "OK"


class BannerHandler(AdSelectHandler):
    """
        Select appropriate banners to display.
    """

    @handler.exportRPC()
    def select(self, impression_params):
        return selector_utils.select_banner(impression_params)


def configure_iface(port = iface_config.SERVER_PORT):
    factory = JSONRPCServerFactory()
    factory.addHandler(CampaignUpdateHandler(), namespace='campaign')
    factory.addHandler(ImpressionsUpdateHandler(), namespace='impression')
    factory.addHandler(BannerHandler(), namespace='banner')
    reactor.listenTCP(port, factory)

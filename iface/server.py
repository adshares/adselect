from adselect.iface import config as iface_config
from twisted.internet import reactor
from txjason.netstring import JSONRPCServerFactory
from txjason import handler


class CampaignUpdateHandler(handler.Handler):
    """
        Update all info about campaign.
    """
    @handler.exportRPC()
    def add(self, campaign_data):
        return "campaign.add"

    @handler.exportRPC()
    def update(self, campaign_data):
        return "campaign.update"

    @handler.exportRPC()
    def delete(self, campaign_id):
        return "campaign.delete"


class ImpressionsUpdateHandler(handler.Handler):
    """
        Update all infos about impressions.
    """

    @handler.exportRPC()
    def add(self, impressions_data_list):
        return "impression.add"


class BannerHandler(handler.Handler):
    """
        Select appropriate banners to display.
    """

    @handler.exportRPC()
    def select(self, impression_params):
        return "banner.select"


def configure_iface(port = iface_config.SERVER_PORT):
    factory = JSONRPCServerFactory()
    factory.addHandler(CampaignUpdateHandler(), namespace='campaign')
    factory.addHandler(ImpressionsUpdateHandler(), namespace='impression')
    factory.addHandler(BannerHandler(), namespace='banner')
    reactor.listenTCP(port, factory)

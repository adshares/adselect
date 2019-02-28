import logging

from fastjsonrpc.jsonrpc import JSONRPCError
from fastjsonrpc.server import JSONRPCServer
from jsonobject.exceptions import BadValueError
from twisted.internet import defer, reactor
from twisted.web.server import Site

from adselect.iface import const as iface_const, protocol as iface_proto, utils as iface_utils


class AdSelectIfaceServer(JSONRPCServer):
    """
    JSON-RPC endpoint.
    """
    def __init__(self):
        JSONRPCServer.__init__(self)
        self.logger = logging.getLogger(__name__)

    # campaign interface
    @defer.inlineCallbacks
    def jsonrpc_campaign_update(self, *campaign_data_list):
        """
        JSON-RPC campaign_update method handler.

        :param campaign_data_list: List of campaign data.
        :return: True
        """
        if not campaign_data_list:
            yield self.logger.warning("No campaign data to update.")
            defer.returnValue(True)
        else:
            for campaign_data in campaign_data_list:
                yield self.logger.debug("Campaign update: {0}".format(campaign_data))
                try:
                    campaign_data['filters'] = iface_proto.RequireExcludeObject(**campaign_data['filters'])

                    campaign_data['banners'] = [iface_proto.BannerObject(campaign_id=campaign_data['campaign_id'], **b) for b in
                                                campaign_data['banners']]

                    yield iface_utils.create_or_update_campaign(iface_proto.CampaignObject(**campaign_data))
                except (KeyError, TypeError, BadValueError) as e:
                    raise JSONRPCError(e, iface_const.INVALID_OBJECT)

        defer.returnValue(True)

    @defer.inlineCallbacks
    def jsonrpc_campaign_delete(self, *campaign_id_list):
        """
        JSON-RPC campaign_delete method handler.

        :param campaign_id_list: List of campaign identifiers.
        :return: True
        """
        if not campaign_id_list:
            yield self.logger.warning("No campaign id to remove.")
            defer.returnValue(True)
        else:
            for campaign_id in campaign_id_list:
                yield self.logger.info("Campaign removal: {0}".format(campaign_id))
                yield iface_utils.delete_campaign(campaign_id)
        defer.returnValue(True)

    @defer.inlineCallbacks
    # impressions interface
    def jsonrpc_impression_add(self, *impressions_data_list):
        """
        JSON-RPC impression_add method handler.

        :param impressions_data_list: List of impression data.
        :return: True
        """
        if not impressions_data_list:
            yield self.logger.warning("No event data to add.")
            defer.returnValue(True)
        else:
            for imobj in impressions_data_list:
                yield self.logger.debug("Adding event data: {0}".format(imobj))
                imobj['paid_amount'] = 0
                yield self.logger.debug("Adding event data: {0}".format(imobj))
                try:
                    yield iface_utils.add_impression(iface_proto.ImpressionObject(imobj))
                except BadValueError as e:
                    raise JSONRPCError(e, iface_const.INVALID_OBJECT)

        defer.returnValue(True)

    @defer.inlineCallbacks
    # impressions interface
    def jsonrpc_impression_payment_add(self, *impressions_data_list):
        """
        JSON-RPC impression_add method handler.

        :param impressions_data_list: List of impression data.
        :return: True
        """
        if not impressions_data_list:
            yield self.logger.warning("No event data to add.")
            defer.returnValue(True)
        else:
            for imobj in impressions_data_list:
                yield self.logger.debug("Adding event data: {0}".format(imobj))
                try:
                    yield iface_utils.add_impression(iface_proto.ImpressionObject(imobj),
                                                     increment=False)
                except BadValueError as e:
                    raise JSONRPCError(e, iface_const.INVALID_OBJECT)
        defer.returnValue(True)

    @defer.inlineCallbacks
    # select banner interface
    def jsonrpc_banner_select(self, *impression_param_list):
        """
        JSON-RPC banner_select method handler.

        :param impression_param_list: List of impression parameters
        :return: Selected banners data in JSON.
        """
        def send_respone(responses_dict):

            responses = [iface_proto.SelectBannerResponse(request_id=request_id,
                                                          banner_id=responses_dict[request_id])
                         for request_id in responses_dict]

            return [response.to_json() for response in responses]

        if not impression_param_list:
            yield self.logger.warning("No event parameters.")
            defer.returnValue([])
        else:
            yield self.logger.info("Select banners request received.")
            yield self.logger.debug(impression_param_list)
            try:
                banner_requests = [iface_proto.SelectBannerRequest(impression_param) for impression_param in
                                   impression_param_list]

                selected_banners = iface_utils.select_banner(banner_requests)
                selected_banners.addCallback(send_respone)
                ret_sb = yield selected_banners
            except BadValueError as e:
                raise JSONRPCError(e, iface_const.INVALID_OBJECT)

            defer.returnValue(ret_sb)


def configure_iface(port=iface_const.SERVER_PORT, interface=iface_const.SERVER_INTERFACE):
    """
    Set up Twisted reactor to listen on TCP.

    :param port: Listening port.
    :return: Listening reactor.
    """
    logger = logging.getLogger(__name__)
    logger.info("Initializing interface server on port: {0}".format(port))
    site = Site(AdSelectIfaceServer())
    return reactor.listenTCP(port, site, interface)

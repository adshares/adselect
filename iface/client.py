from twisted.internet import defer, endpoints, task
from txjason.netstring import JSONRPCClientFactory

from adselect.iface import config as iface_config


CAMAPAIGN_DATA = {
    'id':'1',
    'filters':{
        'required':[],
        'excluded':[],
    },
    'banners':[
        {
            'id':'banner_id',
            'keywords':{}
        }
    ]
}

ADDED_IMPRESSION_DATA = {

}

SELECT_IMPRESSION_DATA = {

}


@defer.inlineCallbacks
def main(reactor, description):
    endpoint = endpoints.clientFromString(reactor, description)
    client = JSONRPCClientFactory(endpoint, reactor=reactor)

    #add campaign
    r = yield client.callRemote('campaign.add', [CAMAPAIGN_DATA])
    print "add campaign result", r

    #update campaign
    r = yield client.callRemote('campaign.update', [CAMAPAIGN_DATA])
    print "update campaign result", r

    #update campaign
    r = yield client.callRemote('campaign.delete', [CAMAPAIGN_DATA['id']])
    print "delete campaign result", r

    #add impressions
    r = yield client.callRemote('impression.add', [ADDED_IMPRESSION_DATA])
    print "add impression result", r

    #select banners
    r = yield client.callRemote('banner.select', [SELECT_IMPRESSION_DATA])
    print "select banner result", r


if __name__ == "__main__":
    task.react(main, ['tcp:127.0.0.1:%s' %iface_config.SERVER_PORT])
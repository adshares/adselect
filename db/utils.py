from txmongo import filter

def add_campaigns(cmpobj):
    """
        List of campaign data
        {
            'id':'campaign_id',
            'filters':{
                'required': [{
                    'keyword':'impression_keyword',
                    'filter':'impression_filter'
                }],
                'excluded':[{
                    'keyword':'impression_keyword',
                    'filter':'impression_filter'
                }],
            }
            banners:[
                {
                    'id':'banner_id',
                    'keywords':banner_keywords_dict,
                },
            ]
        }
    """

def update_campaigns(campaigns_list):
    pass


def delete_campaigns(campaigns_ids_list):
    """
        campaigns_ids_list list of id to delete
    """


def add_impressions(impression_list):
    """
        List of impressions with payments
        {
            'id':'banner_id',
            'keywords':'impression_keywords_dict',
            'amount':'paid_amount'
            'userid':''
        }
    """

from twisted.internet import defer, reactor
@defer.inlineCallbacks
def example():
    from adselect.db import get_campaign_collection

    campaign_collection = yield get_campaign_collection()
    idx = filter.sort(filter.ASCENDING("user_id"))
    campaign_collection.campaign.create_index(idx, unique=True)
    result = yield campaign_collection.campaign.index_information()
    print result

if __name__ == "__main__":
    example().addCallback(lambda ign: reactor.stop())
    reactor.run()

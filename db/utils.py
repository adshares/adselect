from twisted.internet import defer, reactor
from txmongo import filter

from adselect import db


@defer.inlineCallbacks
def add_or_update_campaigns(campaign_doc, banners_doc_list):
    campaign_collection = yield db.get_campaign_collection()
    campaign_collection.insert(campaign_doc, safe=True)

    banner_collection = yield  db.get_banner_collection()
    for banner_doc in banners_doc_list:
        banner_collection.insert(banner_doc, safe=True)


@defer.inlineCallbacks
def get_banners_iter(handle_wrapper):
    docs, dfr = yield db.get_banner_collection().find(cursor=True)
    while docs:
        for doc in docs:
            handle_wrapper(doc)
        docs, dfr = yield dfr


def delete_campaigns(campaigns_ids_list):
    """
        campaigns_ids_list list of id to delete
    """


def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    pass


def update_banner_payment(banner_id, pay_per_publisher_per_keyword_dict):
    pass


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
def example():
    from adselect.db import get_mongo_db

    campaign_collection = get_mongo_db().campaign
    idx = filter.sort(filter.ASCENDING("user_id"))
    print campaign_collection.create_index(idx, unique=True)
    #result = yield campaign_collection.index_information()
    #print result


if __name__ == "__main__":
    #add_or_update_campaigns(1, []).addCallback(lambda x: reactor.stop())
    reactor.run()
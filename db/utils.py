from twisted.internet import defer, reactor
from adselect import db


@defer.inlineCallbacks
def add_or_update_campaign(campaign_doc, banners_doc_list):
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


@defer.inlineCallbacks
def get_banner(params, wrapper):
    result = yield db.get_banner_collection().find_one(params)
    wrapper(result)


def delete_campaigns(campaigns_ids_list):
    """
        campaigns_ids_list list of id to delete
    """


@defer.inlineCallbacks
def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    impression_stats_collection = db.get_impressions_stats_collection()
    yield impression_stats_collection.replace_one({"banner_id":banner_id},
                                                           {"stats": counts_per_publisher_dict, "banner_id":banner_id},
                                                           upsert=True)


@defer.inlineCallbacks
def get_banner_impression_count_iter(record_wrapper):
    docs, dfr = yield db.get_impressions_stats_collection().find(cursor=True)
    while docs:
        for doc in docs:
            record_wrapper(doc)
        docs, dfr = yield dfr


@defer.inlineCallbacks
def update_banner_payment(banner_id, pay_per_publisher_per_keyword_dict):
    payments_stats_collections = db.get_payments_stats_collection()
    print pay_per_publisher_per_keyword_dict

    yield payments_stats_collections.replace_one({"banner_id":banner_id},
                                                 {"stats": pay_per_publisher_per_keyword_dict, "banner_id":banner_id},
                                                 upsert=True)


@defer.inlineCallbacks
def get_banner_payment_iter(record_wrapper):
    docs, dfr = yield db.get_payments_stats_collection().find(cursor=True)
    while docs:
        for doc in docs:
            record_wrapper(doc)
        docs, dfr = yield dfr
from twisted.internet import defer

from adselect import db


class query_iterator(object):
    """
        Every query with cursor = True can be iterated with simple way:

        _iter = query_iterator(query)

        while True:
            elem = yield _iter.next()

            if elem is None:
                break

            print "elem", elem
    """

    def __init__(self, query):
        self.query = query

        self.docs, self.dfr = None, None
        self.docs_index = 0

    def __iter__(self):
        return self

    def __next__(self):
        return self.next()

    @defer.inlineCallbacks
    def next(self):
        if self.docs is None:
            self.docs, self.dfr = yield self.query

        if not self.docs:
            defer.returnValue(None)

        if self.docs_index >= len(self.docs):
            self.docs, self.dfr = yield self.dfr
            self.docs_index = 0
            value = yield self.next()
            defer.returnValue(value)

        value = self.docs[self.docs_index]
        self.docs_index += 1
        defer.returnValue(value)


########################
#### CAMPAIGNS #########
########################

@defer.inlineCallbacks
def get_campaign(campaign_id):
    campaign_collection = yield db.get_campaign_collection()
    return_value = yield campaign_collection.find_one({'campaign_id': campaign_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_campaigns_iter():
    campaign_collection = yield db.get_campaign_collection()
    return_value = yield campaign_collection.find(cursor=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_campaign(campaign_id, time_start, time_end, filters, keywords):
    campaign_collection = yield db.get_campaign_collection()
    return_value = yield campaign_collection.replace_one({'campaign_id': campaign_id},{
        'campaign_id':campaign_id,
        'time_start':time_start,
        'time_end':time_end,
        'filters':filters,
        'keywords':keywords
    }, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign(campaign_id):
    campaign_collection = yield db.get_campaign_collection()
    return_value = yield campaign_collection.delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)


#########################
##### BANNERS ###########
#########################
@defer.inlineCallbacks
def get_banners_iter():
    banner_collection = yield db.get_banner_collection()
    defer.returnValue(query_iterator(banner_collection.find(cursor=True)))


@defer.inlineCallbacks
def get_banner(banner_id):
    banner_collection = yield db.get_banner_collection()
    defer.returnValue(banner_collection.find_one({'banner_id': banner_id}))


@defer.inlineCallbacks
def update_banner(banner_id, campaign_id, banner_size, keywords):
    banner_collection = yield db.get_banner_collection()
    return_value = yield banner_collection.replace_one({'banner_id': banner_id},{
        'banner_id':banner_id,
        'campaign_id':campaign_id,
        'banner_size':banner_size,
        'keywords':keywords
    }, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_campaign_banners(campaign_id):
    banner_collection = yield db.get_banner_collection()
    return_value = yield banner_collection.find({'campaign_id': campaign_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_campaign_banners(campaign_id):
    banner_collection = yield db.get_banner_collection()
    return_value = yield banner_collection.delete_many({'campaign_id': campaign_id})
    defer.returnValue(return_value)


############################
##### STATS ################
############################


# IMPRESSION COUNTS
@defer.inlineCallbacks
def get_banner_impression_count(banner_id):
    impressions_stats_collection = yield db.get_impressions_stats_collection()
    return_value = yield  impressions_stats_collection.find_one({'banner_id': banner_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def get_banner_impression_count_iter():
    impressions_stats_collection = yield db.get_impressions_stats_collection()
    defer.returnValue(query_iterator(impressions_stats_collection.find(cursor=True)))


@defer.inlineCallbacks
def update_banner_impression_count(banner_id, counts_per_publisher_dict):
    impression_stats_collection = yield  db.get_impressions_stats_collection()
    return_value = yield impression_stats_collection.replace_one({"banner_id": banner_id},{
        "banner_id": banner_id,
        "stats": counts_per_publisher_dict},
    upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_impression_count(banner_id):
    impressions_stats_collection = yield db.get_impressions_stats_collection()
    return_value = yield impressions_stats_collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# PAYMENTS
@defer.inlineCallbacks
def get_banner_payment(banner_id):
    payments_stats_collection = yield db.get_payments_stats_collection()
    return_value = yield payments_stats_collection.find_one({'banner_id': banner_id})
    defer.returnValue(return_value)


@defer.inlineCallbacks
def update_banner_payment(banner_id, pay_per_publisher_per_size_per_keyword_dict):
    payments_stats_collections = yield db.get_payments_stats_collection()
    return_value = yield payments_stats_collections.replace_one({"banner_id": banner_id}, {
        "banner_id": banner_id,
        "stats": pay_per_publisher_per_size_per_keyword_dict
    }, upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_payments(banner_id):
    payments_stats_collection = yield db.get_payments_stats_collection()
    return_value = yield payments_stats_collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)


# SCORES
@defer.inlineCallbacks
def get_banner_scores_iter():
    scores_stats_collection = yield db.get_scores_stats_collection()
    defer.returnValue(query_iterator(scores_stats_collection.find(cursor=True)))


@defer.inlineCallbacks
def update_banner_scores(banner_id, score_per_publisher_per_keyword_dict):
    score_stats_collection = yield db.get_scores_stats_collection()
    return_value =  yield score_stats_collection.replace_one({'banner_id': banner_id},{
        'banner_id': banner_id,
        'stats': score_per_publisher_per_keyword_dict},upsert=True)
    defer.returnValue(return_value)


@defer.inlineCallbacks
def delete_banner_scores(banner_id):
    scores_stats_collection = yield db.get_scores_stats_collection()
    return_value = scores_stats_collection.delete_many({'banner_id': banner_id})
    defer.returnValue(return_value)

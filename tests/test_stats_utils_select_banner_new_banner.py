import time

from twisted.internet import defer

from adselect.iface import server as iface_server
from adselect.stats import tasks as stats_tasks, utils as stats_utils, cache as stats_cache
from tests import db_test_case


class TestSelectBanner(db_test_case):

    @defer.inlineCallbacks
    def test_select_banner(self):

        campaign = {
            "campaign_id": "campaign_id",
            "time_start": 0,
            "time_end": int(time.time()) + 3600,
            "filters": {"required": {}, "excluded": {}},
            "banners": []
        }

        for i in range(9):
            campaign['banners'].append({"banner_id": str(i),
                                        "keywords": {"popular": "yes", "test": "yes"},
                                        "banner_size": "100x100"})

        campaign['banners'].append({"banner_id": str(9),
                                    "keywords": {"popular": "no", "test": "yes"},
                                    "banner_size": "100x100"})

        adselect = iface_server.AdSelectIfaceServer()

        yield adselect.jsonrpc_campaign_update(campaign)

        for banner in range(9):
            for i in range(10):

                event = {'keywords': {"popular": "yes"},
                         'user_id': 'user',
                         'publisher_id': 'pub',
                         'banner_id': str(banner),
                         'event_id': 'event_{0}_{1}'.format(banner, i),
                         'paid_amount': 1}

                yield adselect.jsonrpc_impression_add(event)
                yield adselect.jsonrpc_impression_payment_add(event)

        # Verify impression count
        for banner in range(9):
            self.assertEquals(stats_cache.IMPRESSIONS_COUNT[str(banner)][u'pub'], 10)

        self.assertEquals(stats_cache.IMPRESSIONS_COUNT[u'10'][u'pub'], 0)

        yield stats_utils.load_banners()

        # We have to load all impressions to keep information whether we can/can't display new banners for publisher.
        yield stats_utils.load_impression_counts()

        # We do not load payments as it is kept per calculation round.

        # Load best keywords taking into account scores.
        yield stats_utils.load_scores()

        selected = yield adselect.jsonrpc_banner_select({'banner_size': '100x100',
                                                         'banner_filters': {'require': {},
                                                                            'exclude': {}},
                                                         'user_id': 'user',
                                                         'request_id': 1,
                                                         'publisher_id': 'pub',
                                                         'keywords': {"test": "yes"}})

        self.assertNotEquals(str(10), selected[0]['banner_id'])

        # Load banners.
        yield stats_utils.load_banners()

        # Recalculate KEYWORDS_BANNERS and BEST_KEYWORDS.
        scores_stats = yield stats_tasks.save_banner_scores()

        # Taking from database BANNERS_IMPRESSIONS_COUNT.
        yield stats_tasks.save_impression_count()
        # Taking from database KEYWORD_IMPRESSION_PAID_AMOUNT.
        yield stats_tasks.save_keyword_payments()

        # Load impression count
        yield stats_utils.load_impression_counts()

        # Load scores
        yield stats_utils.load_scores(scores_stats)

        # Verify impression count
        for banner in range(9):
            self.assertEquals(10, stats_cache.IMPRESSIONS_COUNT[str(banner)][u'pub'])

        self.assertEquals(0, stats_cache.IMPRESSIONS_COUNT[u'10'][u'pub'])

        selected = yield adselect.jsonrpc_banner_select({'banner_size': '100x100',
                                                         'banner_filters': {'require': {},
                                                                            'exclude': {}},
                                                         'user_id': 'user',
                                                         'request_id': 1,
                                                         'publisher_id': 'pub',
                                                         'keywords': {"test": "yes"}})

        self.assertNotEquals(str(10), selected[0]['banner_id'])

        campaign = {
            "campaign_id": "campaign_id",
            "time_start": 0,
            "time_end": 9999999999,
            "filters": {"required": {}, "excluded": {}},
            "banners": [{"banner_id": str(10),
                         "keywords": {"popular": "no"},
                         "banner_size": "100x100"}]
        }

        yield adselect.jsonrpc_campaign_update(campaign)

        yield stats_utils.load_banners()
        # Load impression count
        yield stats_utils.load_impression_counts()

        # Load scores
        yield stats_utils.load_scores(scores_stats)

        selected = yield adselect.jsonrpc_banner_select({'banner_size': '100x100',
                                                         'banner_filters': {'require': {},
                                                                            'exclude': {}},
                                                         'user_id': 'user',
                                                         'request_id': 1,
                                                         'publisher_id': 'pub',
                                                         'keywords': {"test": "yes"}})

        self.assertEquals(str(10), selected[0]['banner_id'])

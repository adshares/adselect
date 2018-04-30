from collections import defaultdict
from copy import deepcopy
import time
from twisted.internet import defer
import twisted

from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
from adselect.db import utils as db_utils
from adselect.stats import tasks as stats_tasks

from tests import db_test_case


class TasksTestCase(db_test_case):

    @defer.inlineCallbacks
    def test_save_keyword_payments(self):

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        for imp in self.impressions:

            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']

            yield stats_utils.process_impression(**imp)
            payments = yield db_utils.get_banner_payment(imp['banner_id'])
            self.assertIsNone(payments)

        KEYWORD_IMPRESSION_PAID_AMOUNT = yield deepcopy(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT)

        yield stats_tasks.save_keyword_payments()

        yield self.assertNotEqual(KEYWORD_IMPRESSION_PAID_AMOUNT,
                                  stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT)

        for imp in self.impressions:
            payments = yield db_utils.get_banner_payment(imp['banner_id'])
            self.assertIsNotNone(payments)
            yield stats_utils.process_impression(**imp)

        yield stats_tasks.save_keyword_payments()

    @defer.inlineCallbacks
    def test_save_new_banner_scores(self):
        stats_cache.IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(lambda: int(0)))
        stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT = defaultdict(lambda: defaultdict(lambda: defaultdict(lambda: float(0.0))))

        self.campaigns[0]['time_end'] = 10

        for campaign in self.campaigns:
            yield db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        for imp in self.impressions:

            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']

            yield stats_utils.process_impression(**imp)
            score = yield db_utils.get_banner_scores(imp['banner_id'])
            self.assertIsNone(score)

        yield stats_tasks.save_new_banner_scores(set())

        for imp in self.impressions:
            active = yield stats_utils.is_banner_active(imp['banner_id'])
            score = yield db_utils.get_banner_scores(imp['banner_id'])
            if active:
                yield self.assertIsNotNone(score)
            else:
                yield self.assertIsNone(score)

    @defer.inlineCallbacks
    def test_save_banner_scores(self):
        stats_cache.IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(lambda: int(0)))
        stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT = defaultdict(lambda: defaultdict(lambda: defaultdict(lambda: float(0.0))))

        self.campaigns[-1]['time_end'] = 10

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        for imp in self.impressions:

            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']

            yield stats_utils.process_impression(**imp)
            score = yield db_utils.get_banner_scores(imp['banner_id'])
            self.assertIsNone(score)

        stats_tasks.save_banner_scores()

        for imp in self.impressions:
            yield stats_utils.process_impression(**imp)
            score = yield db_utils.get_banner_scores(imp['banner_id'])
            yield self.assertIsNotNone(score)

        stats_tasks.save_banner_scores()

        for imp in self.impressions:
            score = yield db_utils.get_banner_scores(imp['banner_id'])
            yield self.assertIsNotNone(score)

    @defer.inlineCallbacks
    def test_clean_database(self):

        for campaign in self.campaigns:
            campaign['time_end'] = 10
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        yield stats_tasks.clean_database()

        for coll_name in ['campaign', 'banner', 'impression']:

            banners, dfr = yield db_utils.get_collection_iter(coll_name)
            self.assertEqual(len(banners), 0)

    @defer.inlineCallbacks
    def test_calculate_last_round_score(self):
        banner_stats = {
            'pub_Page': {'Hurf_Srinivasan': 0.39, 'Kamel_Thierry': 0.39, 'Pratap_Fletcher': 0.39, 'Lori_Hume': 0.39,
                         'Charles_Coleen': 0.39}}

        stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT['banner_id'] = banner_stats
        stats_cache.IMPRESSIONS_COUNT['banner_id']['pub_Page'] = 2

        for publisher_id in banner_stats:
            publisher_db_impression_count = 1

            for keyword, score_value in banner_stats.get(publisher_id, {}).iteritems():

                last_round_score = stats_tasks.calculate_last_round_score(publisher_id, 'banner_id', keyword, publisher_db_impression_count)
                yield self.assertGreaterEqual(last_round_score, score_value)

        stats_cache.IMPRESSIONS_COUNT['banner_id']['pub_Page'] = 0
        for publisher_id in banner_stats:
            publisher_db_impression_count = 0

            for keyword, score_value in banner_stats.get(publisher_id, {}).iteritems():

                last_round_score = stats_tasks.calculate_last_round_score(publisher_id, 'banner_id', keyword, publisher_db_impression_count)
                yield self.assertEqual(last_round_score, 0)

    def test_configure_tasks(self):

        stats_tasks.configure_tasks()
        ret = twisted.internet.reactor.getDelayedCalls()
        self.assertEqual(len(ret), 1)
        call_time = ret[0].getTime()
        self.assertGreaterEqual(call_time, time.time())

        ret[0].cancel()

    def test_configure_tasks(self):

        stats_tasks.recalculate_stats()
        ret = twisted.internet.reactor.getDelayedCalls()
        self.assertEqual(len(ret), 1)
        call_time = ret[0].getTime()
        self.assertGreaterEqual(call_time, time.time())

        ret[0].cancel()

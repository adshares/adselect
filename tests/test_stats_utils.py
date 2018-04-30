import re
from collections import defaultdict

from mock import patch, Mock, MagicMock

from twisted.internet import defer

from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
from adselect.stats import tasks as stats_tasks

from adselect.db import utils as db_utils

import time
from tests import db_test_case


class StatsUtilsTestCase(db_test_case):

    def test_process_impression(self):

        banner_id = 'banner_id'
        publisher_id = 'publisher_id'
        impression_keywords = {'one': 'two', 'three': 'four'}

        self.assertEqual(stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id], 0)

        for key, val in impression_keywords.items():
            stat_key = stats_utils.genkey(key, val)
            self.assertEqual(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][stat_key], 0)

        stats_utils.process_impression(banner_id, publisher_id, impression_keywords,
                                       paid_amount=0.5)

        self.assertEqual(stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id], 1)

        for key, val in impression_keywords.items():
            stat_key = stats_utils.genkey(key, val)

        self.assertEqual(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][stat_key], 1)

        stats_utils.process_impression(banner_id, publisher_id, impression_keywords,
                                       paid_amount=0.0)

        self.assertEqual(stats_cache.IMPRESSIONS_COUNT[banner_id][publisher_id], 2)

        for key, val in impression_keywords.items():
            stat_key = stats_utils.genkey(key, val)

        self.assertEqual(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id][publisher_id][stat_key], 1)

    def test_genkey(self):
        # Test for no '.'
        generated_key = stats_utils.genkey('key', 'value')
        self.assertFalse(re.search('\.', generated_key))

        generated_key = stats_utils.genkey('key', '...value..')
        self.assertFalse(re.search('\.', generated_key))

    def test_is_campaign_active(self):

        timestamp = int(time.time())

        campaign_doc = {'time_start': timestamp - 100000,
                        'time_end': timestamp + 100000}

        self.assertTrue(stats_utils.is_campaign_active(campaign_doc))

        campaign_doc = {'time_start': timestamp + 100000,
                        'time_end': timestamp + 110000}

        self.assertFalse(stats_utils.is_campaign_active(campaign_doc))

        campaign_doc = {'time_start': timestamp - 100000,
                        'time_end': timestamp - 1000}

        self.assertFalse(stats_utils.is_campaign_active(campaign_doc))

    @patch('adselect.stats.utils.load_banners', Mock())
    @patch('adselect.stats.utils.load_impression_counts', Mock())
    @patch('adselect.stats.utils.load_scores', Mock())
    def test_initialize_stats(self):
        stats_utils.initialize_stats()

    @defer.inlineCallbacks
    def test_is_banner_active(self):

        banner_id = 'banner_id'
        banner_doc = {'banner_id': banner_id,
                      'campaign_id': 'campaign_id'}

        campaign_doc = {'campaign_id': 'campaign_id',
                        'time_start': int(time.time() - 1000),
                        'time_end': int(time.time() + 1000)}

        mock_db_utils = MagicMock()
        mock_db_utils.get_banner.return_value = banner_doc
        mock_db_utils.get_campaign.return_value = campaign_doc

        self.patch(stats_utils, 'db_utils', mock_db_utils)

        # All ok

        result = yield stats_utils.is_banner_active(banner_id)
        self.assertTrue(result)

        result = yield stats_utils.is_banner_active(banner_doc)
        self.assertTrue(result)

        # Campaign is wrong
        mock_db_utils.get_campaign.return_value = None
        result = yield stats_utils.is_banner_active(banner_id)
        self.assertFalse(result)

        # Banner id is wrong
        mock_db_utils.get_banner.return_value = None
        result = yield stats_utils.is_banner_active(banner_id)
        self.assertFalse(result)

    @defer.inlineCallbacks
    def test_load_banners(self):

        count = 0
        for ids in stats_cache.BANNERS.values():
            count += len(ids)

        yield self.assertEqual(count, 0)

        banners = defaultdict(list)

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)
                banners[banner['campaign_id']].append(banner['banner_id'])

        yield stats_utils.load_banners()

        count = 0
        for ids in stats_cache.BANNERS.values():
            count += len(ids)

        banner_count = 0
        for ids in banners.values():
            banner_count += len(ids)

        yield self.assertEqual(count, banner_count)

    @defer.inlineCallbacks
    def test_update_banner(self):

        stats_cache.IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(lambda: int(0)))

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        imp_count = defaultdict(lambda: defaultdict(lambda: int(0)))

        for imp in self.impressions:
            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']
            stats_utils.process_impression(**imp)
            bid = imp['banner_id']
            pid = imp['publisher_id']
            imp_count[bid][pid] += 1

        for imp in self.impressions:
            bid = imp['banner_id']
            pid = imp['publisher_id']

            self.assertEqual(stats_cache.IMPRESSIONS_COUNT[bid][pid],
                             imp_count[bid][pid])

    @defer.inlineCallbacks
    def test_process_impression(self):

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        imp_count = defaultdict(lambda: defaultdict(lambda: int(0)))

        for imp in self.impressions:

            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']

            stats_utils.process_impression(**imp)
            bid = imp['banner_id']
            pid = imp['publisher_id']
            imp_count[bid][pid] += 1

        for imp in self.impressions:
            bid = imp['banner_id']
            pid = imp['publisher_id']

            self.assertEqual(stats_cache.IMPRESSIONS_COUNT[bid][pid],
                             imp_count[bid][pid])

            self.assertEqual(sum(stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[bid][pid].values()),
                             len(imp['impression_keywords']) * imp['paid_amount'])

        yield stats_tasks.save_impression_count()
        stats_cache.IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(lambda: int(0)))
        yield stats_utils.load_impression_counts()

    @defer.inlineCallbacks
    def test_load_scores(self):
        self.assertEqual(len(stats_cache.BEST_KEYWORDS), 0)

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        for imp in self.impressions:
            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']
            stats_utils.process_impression(**imp)

        yield stats_tasks.save_banner_scores()
        yield stats_utils.load_scores()

        self.assertNotEqual(len(stats_cache.BEST_KEYWORDS), 0)

        for campaign in self.campaigns:
            yield db_utils.delete_campaign_banners(campaign['campaign_id'])

        yield stats_utils.load_scores()

    @defer.inlineCallbacks
    def test_select_new_banners(self):

        banner_sizes = set()

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                banner_sizes.update([banner['banner_size']])
                yield db_utils.update_banner(banner)

                banner['campaign_id'] += '_2'
                banner['banner_id'] += '_2'
                yield db_utils.update_banner(banner)

                banner['campaign_id'] += '_3'
                banner['banner_id'] += '_3'
                yield db_utils.update_banner(banner)

        stats_utils.load_banners()

        selected = yield stats_utils.select_new_banners('pub1',
                                                        '1x1',
                                                        0)
        self.assertFalse(selected)

        selected = yield stats_utils.select_new_banners('pub1',
                                                        '1x1',
                                                        1)
        self.assertFalse(selected)

        for size in banner_sizes:
            selected = yield stats_utils.select_new_banners('pub1',
                                                            size,
                                                            2,
                                                            filtering_population_factor=1)

            self.assertTrue(selected)

            selected = yield stats_utils.select_new_banners('pub1',
                                                            size,
                                                            1,
                                                            filtering_population_factor=1)

            self.assertTrue(selected)

    @defer.inlineCallbacks
    def test_select_best_banners(self):

        banner_sizes = set()

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                banner_sizes.update([banner['banner_size']])
                yield db_utils.update_banner(banner)

                banner['campaign_id'] += '_2'
                banner['banner_id'] += '_2'
                yield db_utils.update_banner(banner)

                banner['campaign_id'] += '_3'
                banner['banner_id'] += '_3'
                yield db_utils.update_banner(banner)

        for imp in self.impressions:
            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']
            stats_utils.process_impression(**imp)

        stats_utils.load_banners()

        stats_tasks.save_impression_count()
        stats_tasks.save_keyword_payments()
        stats_tasks.save_new_banner_scores()
        stats_tasks.save_banner_scores()

        stats_utils.load_scores()

        stats_utils.load_banners()

        stats_tasks.save_impression_count()
        stats_tasks.save_keyword_payments()
        stats_tasks.save_new_banner_scores()
        stats_tasks.save_banner_scores()

        stats_utils.load_scores()

        for pub_id in [self.impressions[i]['publisher_id'] for i in xrange(len(self.impressions))]:

            selected = yield stats_utils.select_best_banners(pub_id,
                                                             '1x1',
                                                             {})
            self.assertFalse(selected)

            for size in banner_sizes:
                selected = yield stats_utils.select_best_banners(pub_id,
                                                                 size,
                                                                 {})

                self.assertTrue(selected)

            for size in banner_sizes:

                if stats_cache.BEST_KEYWORDS[pub_id][size]:

                    impression_keywords = {}
                    for keyword in stats_cache.BEST_KEYWORDS[pub_id][size]:
                        key, value = keyword.split('_')
                        impression_keywords[key] = value

                    selected = yield stats_utils.select_best_banners(pub_id,
                                                                     size,
                                                                     impression_keywords,
                                                                     )
                    self.assertTrue(selected)

                    selected = yield stats_utils.select_best_banners(pub_id,
                                                                     size,
                                                                     impression_keywords,
                                                                     1)
                    self.assertTrue(selected)

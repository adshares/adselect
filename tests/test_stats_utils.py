import re

from mock import patch, Mock, MagicMock

from twisted.trial import unittest
from twisted.internet import defer

from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils
import time


class StatsUtilsTestCase(unittest.TestCase):

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

    def test_campaign_active(self):

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

import re
import time

from twisted.trial import unittest

from adselect.stats import cache as stats_cache
from adselect.stats import utils as stats_utils


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

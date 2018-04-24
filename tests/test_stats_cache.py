from twisted.trial import unittest

from adselect.stats import cache as stats_cache


class CacheTestCase(unittest.TestCase):

    def test_delete_impression_count(self):

        stats_cache.IMPRESSIONS_COUNT['banner_id'] = {'publisher1': 1}

        stats_cache.delete_impression_count('banner_id')
        self.assertTrue('banner_id' not in stats_cache.IMPRESSIONS_COUNT)

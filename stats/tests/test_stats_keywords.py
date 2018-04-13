from copy import deepcopy
from unittest import TestCase

from adselect.stats import cache as stats_cache


class TestSetKeywords(TestCase):

    def test_set_best_keywords(self):

        keywords = ['keyword1', 'keyword2']

        stats_cache.set_best_keywords(publisher_id='publisher1',
                                      banner_size='100x100',
                                      keywords_list=keywords)

        self.assertEqual(stats_cache.BEST_KEYWORDS['publisher1']['100x100'], keywords)

    def test_get_best_keywords(self):

        self.assertEqual(stats_cache.get_best_keywords('publisher1', '100x100'), [])

        keywords = ['keyword1', 'keyword2']

        stats_cache.set_best_keywords(publisher_id='publisher1',
                                      banner_size='100x100',
                                      keywords_list=keywords)

        self.assertEqual(stats_cache.get_best_keywords('publisher1', '100x100'), keywords)

    def test_delete_best_keywords(self):

        keywords = ['keyword1', 'keyword2']

        stats_cache.set_best_keywords(publisher_id='publisher1',
                                      banner_size='100x100',
                                      keywords_list=keywords)

        stats_cache.delete_best_keywords()

        self.assertEqual(len(stats_cache.BEST_KEYWORDS), 0)
        self.assertEqual(stats_cache.get_best_keywords('publisher1', '100x100'), [])


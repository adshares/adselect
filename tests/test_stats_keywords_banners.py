from copy import deepcopy
from unittest import TestCase

from adselect.stats import cache as stats_cache


class TestSetKeywordsBanners(TestCase):

    KEYWORDS_BANNERS = deepcopy(stats_cache.KEYWORDS_BANNERS)

    def setUp(self):
        stats_cache.KEYWORDS_BANNERS = deepcopy(self.KEYWORDS_BANNERS)

    def test_get_keyword_banners(self):

        self.assertEqual(len(stats_cache.KEYWORDS_BANNERS['publisher1']['100x100']), 0)

        stats_cache.add_keyword_banner(publisher_id='publisher1',
                                       banner_size='100x100',
                                       keyword='keyword1',
                                       keyword_score=0.5,
                                       banner_id='banner1',
                                       limit=100)

        self.assertEqual(stats_cache.KEYWORDS_BANNERS['publisher1']['100x100'],
                         {'keyword1': [(0.5, 'banner1')]})

    def test_add_keyword_banner(self):

        self.assertEqual(len(stats_cache.KEYWORDS_BANNERS['publisher1']['100x100']), 0)

        stats_cache.add_keyword_banner(publisher_id='publisher1',
                                       banner_size='100x100',
                                       keyword='keyword1',
                                       keyword_score=0.5,
                                       banner_id='banner1',
                                       limit=100)

        self.assertEqual(stats_cache.KEYWORDS_BANNERS['publisher1']['100x100']['keyword1'],
                         [(0.5, 'banner1')])

    def test_delete_keyword_banner(self):

        stats_cache.add_keyword_banner(publisher_id='publisher1',
                                       banner_size='100x100',
                                       keyword='keyword1',
                                       keyword_score=0.5,
                                       banner_id='banner1',
                                       limit=100)

        self.assertEqual(stats_cache.KEYWORDS_BANNERS['publisher1']['100x100']['keyword1'],
                         [(0.5, 'banner1')])

        stats_cache.reset_keyword_banners()

        self.assertEqual(stats_cache.KEYWORDS_BANNERS['publisher1']['100x100']['keyword1'], [])

from collections import defaultdict
from copy import deepcopy

from twisted.internet import defer

from adselect.db import utils as db_utils
from adselect.iface import protocol as iface_proto, utils as iface_utils
from adselect.stats import cache as stats_cache
from tests import db_test_case


class CacheUtilsCampaignTestCase(db_test_case):

    KEYWORDS_BANNERS = deepcopy(stats_cache.KEYWORDS_BANNERS)
    IMPRESSION_COUNT = deepcopy(stats_cache.IMPRESSIONS_COUNT)

    @defer.inlineCallbacks
    def test_load_impression_counts(self):

        stats_cache.IMPRESSIONS_COUNT = defaultdict(lambda: defaultdict(lambda: int(0)))

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        for imp in self.impressions:
            yield iface_utils.add_impression(iface_proto.ImpressionObject(imp))
            self.IMPRESSION_COUNT[imp['banner_id']][imp['publisher_id']] += 1

        self.assertEqual(self.IMPRESSION_COUNT, stats_cache.IMPRESSIONS_COUNT)

    def test_delete_impression_count(self):

        stats_cache.IMPRESSIONS_COUNT['banner_id'] = {'publisher1': 1}
        stats_cache.delete_impression_count('banner_id')
        self.assertTrue('banner_id' not in stats_cache.IMPRESSIONS_COUNT)

    def test_get_keyword_banners(self):
        stats_cache.KEYWORDS_BANNERS = deepcopy(self.KEYWORDS_BANNERS)
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
        stats_cache.KEYWORDS_BANNERS = deepcopy(self.KEYWORDS_BANNERS)
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
        stats_cache.KEYWORDS_BANNERS = deepcopy(self.KEYWORDS_BANNERS)
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

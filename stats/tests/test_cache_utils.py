from twisted.internet import defer

from adselect.data_generators import banner_generator, impression_generator
from adselect.stats.tests import StatsTestCase
from adselect.stats import utils as stats_utils
from adselect.stats import cache as stats_cache
from adselect.iface import utils as iface_utils
from adselect.iface import protocol as iface_proto
from adselect.db import utils as db_utils


class CacheUtilsCampaignTestCase(StatsTestCase):

    @defer.inlineCallbacks
    def test_load_banners(self):

        for banner in banner_generator(10):
            yield db_utils.update_banner(banner)

        yield stats_utils.load_banners()

        count = 0
        for ids in stats_cache.BANNERS.values():
            count += len(ids)

        self.assertEqual(count, 10)

    @defer.inlineCallbacks
    def test_load_impression_counts(self):

        for banner in banner_generator(10):
            yield db_utils.update_banner(banner)

            default_impression = {'banner_id': banner['banner_id'],
                                  'publisher_id': 'superstrona'}

            for impression in impression_generator(10, default_impression):
                yield iface_utils.add_impression(iface_proto.ImpressionObject(impression))

            self.assertEqual(10, stats_cache.IMPRESSIONS_COUNT[banner['banner_id']]['superstrona'])

    def test_load_scores(self):
        pass

from twisted.internet import defer

from adselect.contrib.data_generators import banner_generator, impression_generator
from adselect.stats.tests import StatsTestCase
from adselect.stats import utils as stats_utils
from adselect.stats import tasks as stats_tasks

from adselect.stats import const as stats_consts
from adselect.stats import cache as stats_cache
from adselect.iface import utils as iface_utils
from adselect.iface import protocol as iface_proto
from adselect.db import utils as db_utils
import re
from adselect.contrib import utils as contrib_utils
import time
import adselect.contrib.log_setup
import logging


class CacheUtilsCampaignTestCase(StatsTestCase):

    @defer.inlineCallbacks
    def test_save_impression_counts(self):

        logger = logging.getLogger(__name__)

        for banner in banner_generator(10):
            yield db_utils.update_banner(banner)

            default_impression = {'banner_id': banner['banner_id'],
                                  'publisher_id': 'publisher_id'}

            number_of_impressions = 10

            for impression in impression_generator(number_of_impressions, default_impression):
                yield iface_utils.add_impression(iface_proto.ImpressionObject(impression))

            self.assertEqual(number_of_impressions, stats_cache.IMPRESSIONS_COUNT[banner['banner_id']]['publisher_id'])

            yield stats_tasks.save_views()

            impression_count_from_db = yield db_utils.get_banner_impression_count(banner['banner_id'])
            self.assertEqual(number_of_impressions, sum(impression_count_from_db['stats'].values()))

    def test_load_scores(self):
        pass

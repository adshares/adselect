import re

import tests
from adselect.stats import utils as stats_utils
from adselect.contrib import utils as contrib_utils


class StatsUtilsCampaignTestCase(tests.StatsTestCase):

    def test_genkey(self):
        # Test for no '.'
        generated_key = stats_utils.genkey('key', 'value')
        self.assertFalse(re.search('\.', generated_key))

        generated_key = stats_utils.genkey('key', '...value..')
        self.assertFalse(re.search('\.', generated_key))

    def test_campaign_active(self):

        timestamp = contrib_utils.get_timestamp()

        campaign_doc = {'time_start': timestamp - 100000,
                        'time_end': timestamp + 100000}

        self.assertTrue(stats_utils.is_campaign_active(campaign_doc))

        campaign_doc = {'time_start': timestamp + 100000,
                        'time_end': timestamp + 110000}

        self.assertFalse(stats_utils.is_campaign_active(campaign_doc))

        campaign_doc = {'time_start': timestamp - 100000,
                        'time_end': timestamp - 1000}

        self.assertFalse(stats_utils.is_campaign_active(campaign_doc))


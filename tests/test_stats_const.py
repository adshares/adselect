from unittest import TestCase

from adselect.stats import const as stats_const


class TestSetConst(TestCase):

    def test_const(self):

        for const_name in ['RECALCULATE_TASK_SECONDS_INTERVAL',
                           'NEW_BANNERS_IMPRESSION_CUTOFF',
                           'SELECTED_BANNER_MAX_AMOUNT',
                           'NEW_BANNERS_MIX',
                           'NEW_BANNERS_POOL_SIZE_FACTOR']:
            c = getattr(stats_const, const_name)
            self.assertIsNot(c, None)
            self.assertGreaterEqual(c, 0)

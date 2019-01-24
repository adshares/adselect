from unittest import TestCase

from adselect.stats import const as stats_const


class TestSetConst(TestCase):

    def test_const(self):

        self.assertIsNot(stats_const.NEW_BANNERS_IMPRESSION_CUTOFF, None)
        self.assertIsNot(stats_const.RECALCULATE_TASK_SECONDS_INTERVAL, None)

from twisted.internet import defer

import tests
from adselect.db import utils as db_utils


class DBTestCase(tests.DBTestCase):
    @defer.inlineCallbacks
    def test_payments(self):
        yield db_utils.update_banner_scores('banner1',
                                            {'publisher1': {'120x700': {'dogs': 1.0}}})

        impressions_stats = yield db_utils.get_banner_scores('banner1')
        self.assertEqual(impressions_stats['stats']['publisher1']['120x700']['dogs'], 1)

        yield db_utils.delete_banner_scores('banner1')

        impressions_stats = yield db_utils.get_banner_scores('banner1')
        self.assertIsNone(impressions_stats)

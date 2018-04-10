from twisted.internet import defer

from adselect.db import tests as db_tests
from adselect.db import utils as db_utils


class DBTestCase(db_tests.DBTestCase):
    @defer.inlineCallbacks
    def test_impressions(self):

        yield db_utils.update_banner_impression_count('banner1',
                                                      {'publisher1': 1,
                                                       'publisher2': 3})

        impressions_stats = yield db_utils.get_banner_impression_count('banner1')
        self.assertEqual(impressions_stats['stats']['publisher1'], 1)
        self.assertEqual(impressions_stats['stats']['publisher2'], 3)

        yield db_utils.delete_banner_impression_count('banner1')

        impressions_stats = yield db_utils.get_banner_impression_count('banner1')
        self.assertIsNone(impressions_stats)

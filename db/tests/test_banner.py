from twisted.internet import defer

from adselect.db import tests as db_tests
from adselect.db import utils as db_utils


class DBTestCase(db_tests.DBTestCase):
    @defer.inlineCallbacks
    def test_banner(self):

        banner1 = {
                    'banner_id': 'banner1',
                    'banner_size': '100x200',
                    'keywords': {}
                    }
        banner2 = {
                    'banner_id': 'banner2',
                    'banner_size': '150x250',
                    'keywords': {}
                  }
        banner3 = {
            'banner_id': 'banner3',
            'banner_size': '150x250',
            'keywords': {}
        }

        yield db_utils.update_banner(banner1)
        yield db_utils.update_banner(banner2)
        yield db_utils.update_banner(banner3)

        banner1_doc = yield db_utils.get_banner("banner1")
        self.assertEqual(banner1_doc['banner_id'], "banner1")

        banner2_doc = yield db_utils.get_banner("banner2")
        self.assertEqual(banner2_doc['banner_id'], "banner2")

        banners, dfr = yield db_utils.get_banners_iter()
        while banners:
            for banner_doc in banners:
                self.assertIn(banner_doc['banner_id'], ['banner1', 'banner2', 'banner3'])

            banners, dfr = yield dfr

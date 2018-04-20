from twisted.internet import defer

import tests
from adselect.db import utils as db_utils


class DBTestCase(tests.DBTestCase):
    @defer.inlineCallbacks
    def test_banner(self):

        banner1 = {
                    'banner_id': 'banner1',
                    'banner_size': '100x200',
                    'keywords': {},
                    'campaign_id': 'campaign_id'
                    }
        banner2 = {
                    'banner_id': 'banner2',
                    'banner_size': '150x250',
                    'keywords': {},
                    'campaign_id': 'campaign_id'
                  }
        banner3 = {
            'banner_id': 'banner3',
            'banner_size': '150x250',
            'keywords': {},
            'campaign_id': 'campaign_id'
        }

        yield db_utils.update_banner(banner1)
        yield db_utils.update_banner(banner2)
        yield db_utils.update_banner(banner3)

        banner1_doc = yield db_utils.get_banner("banner1")
        self.assertEqual(banner1_doc['banner_id'], "banner1")

        banner2_doc = yield db_utils.get_banner("banner2")
        self.assertEqual(banner2_doc['banner_id'], "banner2")

        banners, dfr = yield db_utils.get_collection_iter('banner')
        while banners:
            for banner_doc in banners:
                self.assertIn(banner_doc['banner_id'], ['banner1', 'banner2', 'banner3'])

            banners, dfr = yield dfr

        banners = yield db_utils.get_campaign_banners('campaign_id')
        self.assertEqual(len(banners), 3)

        yield db_utils.delete_campaign_banners("campaign_id")
        banners, dfr = yield db_utils.get_collection_iter('banner')
        self.assertFalse(banners)

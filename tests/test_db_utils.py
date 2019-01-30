from twisted.internet import defer

import tests
from adselect.db import utils as db_utils


class DBUtilsTestCase(tests.db_test_case):

    @defer.inlineCallbacks
    def test_campaign(self):
        campaign_data = {
            'campaign_id': 'campaign_id',
            'time_start': 12345,
            'time_end': 34567,
            'filters': {},
            'keywords': {},
            'banners': [
                {
                    'banner_id': 'banner1',
                    'banner_size': '100x200',
                    'keywords': {}
                },
                {
                    'banner_id': 'banner2',
                    'banner_size': '150x250',
                    'keywords': {}
                }
            ],
        }

        yield db_utils.update_campaign(campaign_data)

        campaign_doc = yield db_utils.get_campaign(campaign_data['campaign_id'])
        self.assertEqual(campaign_doc['campaign_id'], campaign_data['campaign_id'])
        self.assertEqual(campaign_doc['time_end'], 34567)

        campaign_data['time_end'] = 48999
        yield db_utils.update_campaign(campaign_data)
        campaign_doc = yield db_utils.get_campaign(campaign_data['campaign_id'])
        self.assertEqual(campaign_doc['time_end'], 48999)

        yield db_utils.delete_campaign('campaign_id')
        campaign_doc = yield db_utils.get_campaign(campaign_data['campaign_id'])
        self.assertIsNone(campaign_doc)

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

    @defer.inlineCallbacks
    def test_payments(self):
        yield db_utils.update_banner_scores('banner1',
                                            {'publisher1': {'120x700': {'dogs': 1.0}}})

        impressions_stats = yield db_utils.get_banner_scores('banner1')
        self.assertEqual(impressions_stats['stats']['publisher1']['120x700']['dogs'], 1)

        yield db_utils.delete_banner_scores('banner1')

        impressions_stats = yield db_utils.get_banner_scores('banner1')
        self.assertIsNone(impressions_stats)

    @defer.inlineCallbacks
    def test_scores(self):

        yield db_utils.update_banner_impression_count('banner1',
                                                      {'publisher1': {'dogs': 1.0}})

        impressions_stats = yield db_utils.get_banner_impression_count('banner1')
        self.assertEqual(impressions_stats['stats']['publisher1']['dogs'], 1)

        yield db_utils.delete_banner_impression_count('banner1')

        impressions_stats = yield db_utils.get_banner_impression_count('banner1')
        self.assertIsNone(impressions_stats)

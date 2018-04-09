from twisted.internet import defer

from adselect.db import tests as db_tests
from adselect.db import utils as db_utils


class DBTestCase(db_tests.DBTestCase):
    @defer.inlineCallbacks
    def test_campaign(self):
        CAMPAIGN_DATA = {
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

        yield db_utils.update_campaign(CAMPAIGN_DATA)

        campaign_doc = yield db_utils.get_campaign(CAMPAIGN_DATA['campaign_id'])
        self.assertEqual(campaign_doc['campaign_id'], CAMPAIGN_DATA['campaign_id'])

        CAMPAIGN_DATA['time_end'] = 48999
        yield db_utils.update_campaign(CAMPAIGN_DATA)
        campaign_doc = yield db_utils.get_campaign(CAMPAIGN_DATA['campaign_id'])
        self.assertEqual(campaign_doc['time_end'], 48999)

        yield db_utils.delete_campaign('campaign_id')
        campaign_doc = yield db_utils.get_campaign(CAMPAIGN_DATA['campaign_id'])
        self.assertIsNone(campaign_doc)

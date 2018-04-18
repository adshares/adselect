from twisted.internet import defer

import tests
from adselect.db import utils as db_utils


class DBTestCase(tests.DBTestCase):
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

        campaign_data['time_end'] = 48999
        yield db_utils.update_campaign(campaign_data)
        campaign_doc = yield db_utils.get_campaign(campaign_data['campaign_id'])
        self.assertEqual(campaign_doc['time_end'], 48999)

        yield db_utils.delete_campaign('campaign_id')
        campaign_doc = yield db_utils.get_campaign(campaign_data['campaign_id'])
        self.assertIsNone(campaign_doc)

from twisted.internet import defer

from adselect.iface.tests import IfaceTestCase
from adselect.db import utils as db_utils
from adselect.iface import utils as iface_utils
from adselect.iface import protocol as iface_proto
import copy


class InterfaceUtilsCampaignTestCase(IfaceTestCase):
    CAMPAIGN_DATA = {
        'campaign_id': 'campaign_id',
        'advertiser_id': 'advertiser_id',
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
        ]
    }

    @defer.inlineCallbacks
    def test_utils(self):

        yield iface_utils.create_or_update_campaign(iface_proto.CamapaignObject(self.CAMPAIGN_DATA))
        campaign = yield db_utils.get_campaign(self.CAMPAIGN_DATA['campaign_id'])
        self.assertEqual(campaign['campaign_id'], self.CAMPAIGN_DATA['campaign_id'])

        yield iface_utils.delete_campaign(self.CAMPAIGN_DATA['campaign_id'])
        campaign = yield db_utils.get_campaign(self.CAMPAIGN_DATA['campaign_id'])
        self.assertIs(campaign, None)


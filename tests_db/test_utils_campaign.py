from twisted.internet import defer

import tests
from adselect.db import utils as db_utils
from adselect.iface import utils as iface_utils
from adselect.iface import protocol as iface_proto


class InterfaceUtilsCampaignTestCase(tests.DBTestCase):

    CAMPAIGN_DATA = {
        'campaign_id': 'campaign_id',
        'advertiser_id': 'advertiser_id',
        'time_start': 12345,
        'time_end': 34567,
        'filters': {},
        'keywords': {},
        'banners': []
    }

    @defer.inlineCallbacks
    def test_campaign_add_and_delete(self):

        yield iface_utils.create_or_update_campaign(iface_proto.CampaignObject(self.CAMPAIGN_DATA))
        campaign = yield db_utils.get_campaign(self.CAMPAIGN_DATA['campaign_id'])
        self.assertEqual(campaign['campaign_id'], self.CAMPAIGN_DATA['campaign_id'])

        yield iface_utils.delete_campaign(self.CAMPAIGN_DATA['campaign_id'])
        campaign = yield db_utils.get_campaign(self.CAMPAIGN_DATA['campaign_id'])
        self.assertIs(campaign, None)


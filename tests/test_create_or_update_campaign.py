from copy import deepcopy

from twisted.internet import defer

from adselect.db import utils as db_utils
from adselect.iface import protocol as iface_proto, utils as iface_utils
from tests import db_test_case


class TestCreate_or_update_campaign(db_test_case):

    @defer.inlineCallbacks
    def test_create_or_update_campaign(self):

        for campaign in self.campaigns:

            orig_campaign = deepcopy(campaign)

            campaign['filters'] = iface_proto.RequireExcludeObject(require=campaign['filters']['require'],
                                                                   exclude=campaign['filters']['exclude'])

            campaign['banners'] = [iface_proto.BannerObject(campaign_id=campaign['campaign_id'], **b) for b in campaign['banners']]

            yield iface_utils.create_or_update_campaign(iface_proto.CampaignObject(**campaign))

            db_campaign = yield db_utils.get_campaign(campaign_id=campaign['campaign_id'])
            banners = orig_campaign['banners']
            del orig_campaign['banners']

            for k, v in orig_campaign.items():
                self.assertEqual(v, db_campaign[k])

            for b in banners:
                db_banner = yield db_utils.get_banner(b['banner_id'])

                for k, v in b.items():
                    self.assertEqual(v, db_banner[k])

    @defer.inlineCallbacks
    def test_delete_campaign(self):

        for campaign in self.campaigns:

            campaign['filters'] = iface_proto.RequireExcludeObject(require=campaign['filters']['require'],
                                                                   exclude=campaign['filters']['exclude'])

            campaign['banners'] = [iface_proto.BannerObject(campaign_id=campaign['campaign_id'], **b) for b in campaign['banners']]

            yield iface_utils.create_or_update_campaign(iface_proto.CampaignObject(**campaign))
            db_campaign = yield db_utils.get_campaign(campaign_id=campaign['campaign_id'])
            self.assertIsNotNone(db_campaign)

            yield iface_utils.delete_campaign(campaign_id=campaign['campaign_id'])
            db_campaign = yield db_utils.get_campaign(campaign_id=campaign['campaign_id'])
            self.assertIsNone(db_campaign)

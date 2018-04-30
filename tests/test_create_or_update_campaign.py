from twisted.internet import defer
from copy import deepcopy

from tests import db_test_case
from adselect.iface import protocol as iface_proto
from adselect.iface import utils as iface_utils
from adselect.db import utils as db_utils


class TestCreate_or_update_campaign(db_test_case):

    @defer.inlineCallbacks
    def test_create_or_update_campaign(self):

        for campaign in self.campaigns:

            orig_campaign = deepcopy(campaign)

            required = [iface_proto.KeywordFilterObject(**f) for f in campaign['filters']['require']]
            excluded = [iface_proto.KeywordFilterObject(**f) for f in campaign['filters']['exclude']]

            campaign['filters'] = iface_proto.RequireExcludeListObject(require=required,
                                                                       exclude=excluded)

            campaign['banners'] = [iface_proto.BannerObject(**b) for b in campaign['banners']]

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

            required = [iface_proto.KeywordFilterObject(**f) for f in campaign['filters']['require']]
            excluded = [iface_proto.KeywordFilterObject(**f) for f in campaign['filters']['exclude']]

            campaign['filters'] = iface_proto.RequireExcludeListObject(require=required,
                                                                       exclude=excluded)

            campaign['banners'] = [iface_proto.BannerObject(**b) for b in campaign['banners']]

            yield iface_utils.create_or_update_campaign(iface_proto.CampaignObject(**campaign))
            db_campaign = yield db_utils.get_campaign(campaign_id=campaign['campaign_id'])
            self.assertIsNotNone(db_campaign)

            yield iface_utils.delete_campaign(campaign_id=campaign['campaign_id'])
            db_campaign = yield db_utils.get_campaign(campaign_id=campaign['campaign_id'])
            self.assertIsNone(db_campaign)

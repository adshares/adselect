from tests import db_test_case
from adselect.iface import utils as iface_utils
from mock import MagicMock

from twisted.internet import defer
from adselect.db import utils as db_utils


class TestValidate_banner_with_banner_request(db_test_case):

    @defer.inlineCallbacks
    def test_validate_banner_with_banner_request(self):

        for campaign in self.campaigns:
            db_utils.update_campaign(campaign)

            for banner in campaign['banners']:
                banner['campaign_id'] = campaign['campaign_id']
                yield db_utils.update_banner(banner)

        iface_utils.validate_keywords = MagicMock()
        request = MagicMock()

        for campaign in self.campaigns:
            for banner in campaign['banners']:
                iface_utils.validate_keywords.return_value = True

                validation = yield iface_utils.validate_banner_with_banner_request(request,
                                                                                   banner['banner_id'])
                self.assertTrue(validation)

                iface_utils.validate_keywords.return_value = False
                validation = yield iface_utils.validate_banner_with_banner_request(request,
                                                                                   banner['banner_id'])
                self.assertFalse(validation)

            campaign['time_end'] = 0
            db_utils.update_campaign(campaign)
            for banner in campaign['banners']:

                iface_utils.validate_keywords.return_value = True
                validation = yield iface_utils.validate_banner_with_banner_request(request,
                                                                                   banner['banner_id'])
                self.assertFalse(validation)

            yield db_utils.delete_campaign(campaign['campaign_id'])
            for banner in campaign['banners']:

                iface_utils.validate_keywords.return_value = True
                validation = yield iface_utils.validate_banner_with_banner_request(request,
                                                                                   banner['banner_id'])
                self.assertFalse(validation)


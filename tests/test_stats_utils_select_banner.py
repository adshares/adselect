from copy import deepcopy

from mock import MagicMock
from twisted.internet import defer

from adselect.iface import protocol as iface_proto, utils as iface_utils
from adselect.stats import tasks as stats_tasks, utils as stats_utils
from tests import db_test_case


class TestSelectBanner(db_test_case):

    @defer.inlineCallbacks
    def test_select_banner(self):

        for imp in self.impressions:
            imp['impression_keywords'] = imp['keywords']
            del imp['keywords']
            del imp['user_id']
            del imp['event_id']
            stats_utils.process_impression(**imp)

        stats_utils.load_banners()

        stats_tasks.save_impression_count()
        stats_tasks.save_keyword_payments()
        stats_tasks.save_new_banner_scores()
        stats_tasks.save_banner_scores()

        stats_utils.load_scores()

        for camp in self.campaigns:
            for banner in camp['banners']:

                request = deepcopy(banner)
                request['request_id'] = 1
                request['user_id'] = 'user_id'
                request['publisher_id'] = 'publisher_id'

                response = yield iface_utils.select_banner([iface_proto.SelectBannerRequest(request)])
                self.assertIsNotNone(response)

                mocked_func = stats_utils.select_best_banners
                stats_utils.select_best_banners = MagicMock()
                stats_utils.select_best_banners.return_value = ['banner_id1', 'banner_id2']
                response = yield iface_utils.select_banner([iface_proto.SelectBannerRequest(request)])
                self.assertIsNotNone(response)

                stats_utils.select_best_banners = mocked_func
                mocked_func2 = iface_utils.validate_banner_with_banner_request

                stats_utils.select_best_banners = MagicMock()
                stats_utils.select_best_banners.return_value = ['banner_id1', 'banner_id2']

                iface_utils.validate_banner_with_banner_request = MagicMock()
                iface_utils.validate_banner_with_banner_request.return_value = True

                response = yield iface_utils.select_banner([iface_proto.SelectBannerRequest(request)])
                self.assertIsNotNone(response)

                stats_utils.select_best_banners = mocked_func
                iface_utils.validate_banner_with_banner_request = mocked_func2



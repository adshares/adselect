from twisted.internet import defer

from adselect.iface.tests import WebTestCase


class IfaceWebTestCase(WebTestCase):
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
    def test_add_campaign(self):

        response = yield self.get_response("campaign_update", [self.CAMPAIGN_DATA])

        self.assertIsNotNone(response)
        self.assertTrue(response['result'])


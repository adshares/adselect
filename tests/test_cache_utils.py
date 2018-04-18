import time

from twisted.internet import defer

from adselect.stats.tests import StatsTestCase
from adselect.stats import utils as stats_utils
from adselect.stats import cache as stats_cache
from adselect.iface import utils as iface_utils
from adselect.iface import protocol as iface_proto
from adselect.db import utils as db_utils


class CacheUtilsCampaignTestCase(StatsTestCase):
    campaign = {'time_start': int(time.time()) - 1000,
                'campaign_id': 'Marla',
                'time_end': int(time.time()) + 1000,
                'filters': {'exclude': {},
                            'require': {}},
                'keywords': {}}

    banners = [{'keywords': [{'Juan': 'Bradford'}], 'banner_id': 'Pradeep', 'banner_size': '44x44'},
               {'keywords': [{'Bucky': 'Jennie'}], 'banner_id': 'Malcolm', 'banner_size': '40x40'},
               {'keywords': [{'Bucky': 'Jennie'}], 'banner_id': 'Isidore', 'banner_size': '16x16'},
               {'keywords': [{'Christophe': 'Dennis'}], 'banner_id': 'Lou', 'banner_size': '57x57'},
               {'keywords': [{'Will': 'Jun'}], 'banner_id': 'Stephen', 'banner_size': '36x36'},
               {'keywords': [{'Bucky': 'Jennie'}], 'banner_id': 'Louie', 'banner_size': '50x50'},
               {'keywords': [{'Leif': 'Carol'}], 'banner_id': 'Sandy', 'banner_size': '70x70'},
               {'keywords': [{'Monty': 'Herve'}], 'banner_id': 'Roy', 'banner_size': '41x41'},
               {'keywords': [{'Will': 'Jun'}], 'banner_id': 'Roxanne', 'banner_size': '25x25'},
               {'keywords': [{'Will': 'Jun'}], 'banner_id': 'Joel', 'banner_size': '27x27'}]

    impressions = [{'keywords': {'Jeanette': 'Jeany', 'Panacea': 'Tammy', 'Samir': 'Irwin', 'Heidi': 'Lex', 'Grace': 'Norm'}, 'user_id': 'Maureen', 'banner_id': 'Ofer', 'publisher_id': 'Sanand', 'paid_amount': 0.15604583814873718},
                   {'keywords': {'Luis': 'Owen', 'Johan': 'Raghu', 'Panacea': 'Tammy', 'Mats': 'Geoff', 'Heidi': 'Lex'}, 'user_id': 'Marcos', 'banner_id': 'Tovah', 'publisher_id': 'Diana', 'paid_amount': 0.6299959287229646},
                   {'keywords': {'Jeanette': 'Jeany', 'Luis': 'Owen', 'Panacea': 'Tammy', 'Samir': 'Irwin', 'Heidi': 'Lex'}, 'user_id': 'Werner', 'banner_id': 'Per', 'publisher_id': 'Clare', 'paid_amount': 0.6209730281162932},
                   {'keywords': {'Jeanette': 'Jeany', 'Johan': 'Raghu', 'Panacea': 'Tammy', 'Samir': 'Irwin', 'Heidi': 'Lex'}, 'user_id': 'Arthur', 'banner_id': 'Lenny', 'publisher_id': 'Barbara', 'paid_amount': 0.515500088715111},
                   {'keywords': {'Jeanette': 'Jeany', 'Luis': 'Owen', 'Corey': 'Spock', 'Mats': 'Geoff', 'Grace': 'Norm'}, 'user_id': 'Vladimir', 'banner_id': 'Dominick', 'publisher_id': 'Lea', 'paid_amount': 0.9304439325787556},
                   {'keywords': {'Jeanette': 'Jeany', 'Luis': 'Owen', 'Johan': 'Raghu', 'Panacea': 'Tammy', 'Samir': 'Irwin'}, 'user_id': 'Carol', 'banner_id': 'Kylo', 'publisher_id': 'Debbie', 'paid_amount': 0.9096754209172463},
                   {'keywords': {'Jeanette': 'Jeany', 'Luis': 'Owen', 'Johan': 'Raghu', 'Panacea': 'Tammy', 'Grace': 'Norm'}, 'user_id': 'Indra', 'banner_id': 'Susumu', 'publisher_id': 'List', 'paid_amount': 0.7648266727422341},
                   {'keywords': {'Jeanette': 'Jeany', 'Luis': 'Owen', 'Johan': 'Raghu', 'Grace': 'Norm', 'Samir': 'Irwin'}, 'user_id': 'Carlo', 'banner_id': 'Jarl', 'publisher_id': 'Samuel', 'paid_amount': 0.6491005113651968},
                   {'keywords': {'Francois': 'Darren', 'Luis': 'Owen', 'Corey': 'Spock', 'Grace': 'Norm', 'Heidi': 'Lex'}, 'user_id': 'Slartibartfast', 'banner_id': 'Steven', 'publisher_id': 'Charles', 'paid_amount': 0.5638230023771796},
                   {'keywords': {'Corey': 'Spock', 'Panacea': 'Tammy', 'Grace': 'Norm', 'Johan': 'Raghu', 'Mats': 'Geoff'}, 'user_id': 'Danny', 'banner_id': 'Winston', 'publisher_id': 'Gary', 'paid_amount': 0.7272217228550619}]

    @defer.inlineCallbacks
    def test_load_banners(self):

        db_utils.update_campaign(self.campaign)

        for banner in self.banners:

            default_banner = {'campaign_id': self.campaign['campaign_id']}
            banner.update(default_banner)

            yield db_utils.update_banner(banner)

        yield stats_utils.load_banners()

        count = 0

        for ids in stats_cache.BANNERS.values():
            count += len(ids)

        self.assertEqual(count, 10)

    @defer.inlineCallbacks
    def test_load_impression_counts(self):

        db_utils.update_campaign(self.campaign)

        for banner in self.banners:

            default_banner = {'campaign_id': self.campaign['campaign_id']}
            banner.update(default_banner)

            yield db_utils.update_banner(banner)

            default_impression = {'banner_id': banner['banner_id'],
                                  'publisher_id': 'publisher1'}

            for impression in self.impressions:
                impression.update(default_impression)
                yield iface_utils.add_impression(iface_proto.ImpressionObject(impression))

            self.assertEqual(10, stats_cache.IMPRESSIONS_COUNT[banner['banner_id']]['publisher1'])

    def test_load_scores(self):
        pass
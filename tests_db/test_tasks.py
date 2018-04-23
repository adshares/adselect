import time

from twisted.internet import defer

import tests
from adselect.stats import cache as stats_cache
from adselect.db import utils as db_utils


class TasksTestCase(tests.DBTestCase):
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
    def test_save_keyword_payments(self):

        stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT = {'banner_id': {'publisher1': {'keyword1': 0.5,
                                                                                   'keyword2': 0.9}}}

        for banner_id, payment_stats_dict in stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT.items():
            banner_stats = yield db_utils.get_banner_payment(banner_id)
            db_banner_stats = banner_stats['stats'] if banner_stats else {}

            for publisher_id, publisher_keywords_payment in payment_stats_dict.items():
                if publisher_id not in db_banner_stats:
                    db_banner_stats[publisher_id] = {}

                for keyword, payment_amount in publisher_keywords_payment.items():
                    if keyword not in db_banner_stats[publisher_id]:
                        db_banner_stats[publisher_id][keyword] = 0
                    db_banner_stats[publisher_id][keyword] += payment_stats_dict[publisher_id][keyword]

            yield db_utils.update_banner_payment(banner_id, db_banner_stats)

            # Clear payment stats for another round
            del stats_cache.KEYWORD_IMPRESSION_PAID_AMOUNT[banner_id]

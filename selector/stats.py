import random

# Keep info about best paid keywords for the specific banner size
# BEST_KEYWORDS:{
#   'size1':{
#       'keyword1':['campaignid1_bannerid1', 'campaignid2_bannerid2', ...],
#       'keyword2':['campaignid3_bannerid3', 'campaignid1_bannerid1', ...]
#   },
#    'size2':{
#    }
# }
BEST_KEYWORDS = {}

# Keep info about new banners to display
# NEW_BANNERS:{
#   'size1':['campaignid1_bannerid1', 'campaignid2_bannerid2'],
#   'size2':['campaignid3_bannerid3', 'campaignid1_bannerid1']
# }
NEW_BANNERS = {}

# Keep data about impressions count of new banners
# New banners impression count
# NEW_BANNERS_IMPRESSIONS_COUNT = {
#   'campaignid1_bannerid1':'impression_count',
#   'campaignid2_bannerid2':'impression_count'
# }
NEW_BANNERS_IMPRESSIONS_COUNT = {}


def update_stats():
    pass


def select_new_banners(banner_size, proposition_nb,
                       notpaid_display_cutoff=1000,
                       filtering_population_factor=4
                       ):
    """
        Return banners ids without payment statistic.
        The function doesn't allow to display banners more than notpaid_display_cutoff times without payment.
    """

    try:
        random_banners = random.sample(NEW_BANNERS.get(banner_size, []), proposition_nb*filtering_population_factor)
    except ValueError, e:
        random_banners = []

    # Filter selected banners out banners with were displayd more times than notpaid_display_cutoff
    selected_banners = []
    for banner_id in random_banners:
        if NEW_BANNERS_IMPRESSIONS_COUNT.get(banner_id, 0) < notpaid_display_cutoff:
            selected_banners.append(banner_size)

        if len(selected_banners) > proposition_nb:
            break

    return selected_banners[:proposition_nb]


def select_impression_banners(banner_size,
                              impression_keywords_dict,
                              propositions_nb=100,
                              best_keywords_cutoff=100,
                              banners_per_keyword_cutoff=10,
                              mixed_new_banners_percent=5
                              ):
    """
        Select banners with appropriate size for given impression keywords.
        proposition_nb - the amount of selected banners
        best_keywords_cutoff - cutoff of the best paid keywords taking into account
        banners_per_keyword_cutoff - cutoff of the banners numbers in every seleted keywords
        mixed_new_banners_percent - approximate percentage of new banners in proposed banners list
    """

    #selected best paid impression keywords
    sbpik = set(impression_keywords_dict.keys())&set(BEST_KEYWORDS.get(banner_size, {}).keys()[:best_keywords_cutoff])

    #Select best paid banners with appropriate size
    selected_banners = []
    for selected_keyword in sbpik:
        selected_banners += BEST_KEYWORDS.get(banner_size, {}).get(selected_keyword, [])[:banners_per_keyword_cutoff]
        if len(selected_banners) > propositions_nb:
            break

    # Add new banners without payment statistic
    new_banners_proposition_nb = int(mixed_new_banners_percent*propositions_nb/100.0)
    selected_banners += select_new_banners(banner_size, new_banners_proposition_nb)

    #Shuffle items in the list
    return random.shuffle(selected_banners)[:propositions_nb]
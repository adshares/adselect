import os

#: Wait with recalculation of scores.
RECALCULATE_TASK_SECONDS_INTERVAL = int(os.getenv('ADSELECT_RECALCULATE_TASK_SECONDS_INTERVAL', 5))

#: Limit new banners.
NEW_BANNERS_IMPRESSION_CUTOFF = int(os.getenv('ADSELECT_NEW_BANNERS_IMPRESSION_CUTOFF', 100))

#: The amount of returned banners.
SELECTED_BANNER_MAX_AMOUNT = int(os.getenv('ADSELECT_SELECTED_BANNER_MAX_AMOUNT', 100))
#: Approximate percentage of new banners in proposed banners list.
NEW_BANNERS_MIX = int(os.getenv('ADSELECT_NEW_BANNERS_MIX', 5))

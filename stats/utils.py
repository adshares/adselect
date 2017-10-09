from adselect.contrib import utils as contrib_utils
from adselect.stats import const as stats_consts


def is_campaign_active(campaign_doc):
    timestamp = contrib_utils.get_timestamp()


    # Campaign will not start in this round
    if campaign_doc['time_start'] > timestamp + stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL:
        return False

    # Campaign is finished
    if campaign_doc['time_end'] <= timestamp:
        return False

    return True
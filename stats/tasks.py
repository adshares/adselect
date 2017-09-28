from twisted.internet import task
from twisted.internet import reactor

from adselect.stats import const as stats_consts

def recalculate_stats():
    # Update BEST_KEYWORDS and KEYWORDS_BANNERS based on
    # KEYWORD_IMPRESSION_PAID_AMOUNT and BANNERS_IMPRESSIONS_COUNT

    print "Recalcuating"


def recalculate_stats_task():
    recalculate_stats()
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


def configure_tasks():
    reactor.callLater(stats_consts.RECALCULATE_TASK_SECONDS_INTERVAL, recalculate_stats_task)


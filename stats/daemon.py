from twisted.internet import reactor
from twisted.python import log
import sys

from adselect.stats import tasks as stats_tasks


log.startLogging(sys.stdout)

if __name__ == "__main__":
    stats_tasks.configure_tasks()
    reactor.run()
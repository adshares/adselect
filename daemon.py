import logging.config
import json
import os

from twisted.internet import reactor

from adselect.iface import server as iface_server
from adselect.stats import tasks as stats_tasks
from adselect.stats import utils as stats_utils
from adselect import db


if __name__ == "__main__":

    logging.basicConfig()

    logfile_path = os.path.join(os.environ["ADSELECT_ROOT"], "adselect", "config", "log_config.json")

    with open(logfile_path, "r") as fd:
        logging.config.dictConfig(json.load(fd))

    # Configuring database.
    db.configure_db()

    # Initializing cache from database.
    stats_utils.initialize_stats()

    # Initializing periodic tasks to recalculate scores.
    stats_tasks.configure_tasks()

    # Start http interface to communicate with others Adshares components.
    iface_server.configure_iface()

    # Run.
    reactor.run()

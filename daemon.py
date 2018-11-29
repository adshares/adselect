import json
import logging.config
import os

from twisted.internet import reactor

from adselect import db
from adselect.iface import server as iface_server
from adselect.stats import tasks as stats_tasks, utils as stats_utils

if __name__ == "__main__":

    logging.basicConfig()

    logfile_path = os.path.join(os.getenv('ADSELECT_LOG_CONFIG_FILE', 'config/log_config.json'))

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

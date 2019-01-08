from twisted.internet import reactor

from adselect import db
from adselect.contrib import logs as server_logging
from adselect.iface import server as iface_server
from adselect.stats import tasks as stats_tasks, utils as stats_utils

if __name__ == "__main__":

    # Set up logging.
    server_logging.setup()

    # Configuring database.
    db.configure_db()

    # Initializing cache from database.
    stats_utils.initialize_stats()

    # Initializing periodic tasks to recalculate scores.
    stats_tasks.configure_tasks()

    # Start http interface to communicate with others AdShares components.
    iface_server.configure_iface()

    # Run.
    reactor.run()

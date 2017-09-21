from adselect.iface import server as iface_server
from twisted.internet import reactor
from twisted.python import log

import sys

log.startLogging(sys.stdout)

if __name__ == "__main__":
    iface_server.configure_iface()
    reactor.run()

import os

#: Twisted TCP port number
SERVER_PORT = int(os.getenv('ADSELECT_SERVER_PORT', 8090))

#: JSONRPC error code for invalid data objects
INVALID_OBJECT = -32010

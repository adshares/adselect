import os

#: Twisted TCP port number
SERVER_PORT = int(os.getenv('ADSELECT_SERVER_PORT', 8011))
SERVER_INTERFACE = os.getenv('ADSELECT_SERVER_INTERFACE', '127.0.0.1')

#: JSONRPC error code for invalid data objects
INVALID_OBJECT = -32010

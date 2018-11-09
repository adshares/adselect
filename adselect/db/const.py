import os

#: MongoDB port
MONGO_DB_PORT = int(os.getenv('ADSELECT_MONGO_DB_PORT'))
MONGO_DB_NAME = os.getenv('ADSELECT_MONGO_DB_NAME')

import os

#: MongoDB port
MONGO_DB_PORT = int(os.getenv('ADSELECT_MONGO_DB_PORT', '27017'))
MONGO_DB_NAME = os.getenv('ADSELECT_MONGO_DB_NAME', 'adselect')
MONGO_DB_HOST = os.getenv('ADSELECT_MONGO_DB_HOST', 'localhost')

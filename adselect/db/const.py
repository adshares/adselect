import os

#: MongoDB port, ie. database connection port for AdUser application.
#:
#: `Environmental variable override: ADSELECT_MONGO_DB_PORT`
MONGO_DB_PORT = int(os.getenv('ADSELECT_MONGO_DB_PORT', '27017'))

#: MongoDB database name, ie. database name for ADSELECT application.
#:
#: `Environmental variable override: ADSELECT_MONGO_DB_NAME`
MONGO_DB_NAME = os.getenv('ADSELECT_MONGO_DB_NAME', 'adselect')

#: MongoDB database host, ie. database host for ADSELECT application.
#:
#: `Environmental variable override: ADSELECT_MONGO_DB_HOST`
MONGO_DB_HOST = os.getenv('ADSELECT_MONGO_DB_HOST', 'localhost')

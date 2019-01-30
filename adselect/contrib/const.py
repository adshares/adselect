import os

#: Logging config file (optional), overrides default configuration.
#:
#: `Environmental variable override: ADSELECT_LOG_CONFIG_JSON_FILE`
LOG_CONFIG_JSON_FILE = os.getenv('ADSELECT_LOG_CONFIG_JSON_FILE', None)

#: Logging level
#:
#: `Environmental variable override: ADSELECT_LOG_LEVEL`
LOG_LEVEL = os.getenv('ADSELECT_LOG_LEVEL', 'DEBUG').upper()

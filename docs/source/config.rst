Configuration
=============

Configuration is spread among 5 files:

    * adselect.db.const
    * adselect.iface.const
    * adselect.stats.const
    * config/log_config.json
    * config/supervisord.conf


Banner selection
^^^^^^^^^^^^^^^^

*adselect.stats.const* is a python file containing configuration for the selection calculations.

Database
^^^^^^^^

*adselect.db.const* is a python file containing configuration for the MongoDB.

Interface
^^^^^^^^^

*adselect.iface.const* is a python file containing configuration for the JSON-RPC interface.

Logging
^^^^^^^

*config/log_config.json* contains Python logging configuration. You can learn more about it `here. <https://docs.python.org/2/library/logging.config.html>`_ The AdSelect daemon will look for this file in the ``$ADSELECT_ROOT/aduser/config`` directory, where ``$ADSELECT_ROOT`` is an environmental variable.

Supervisor
^^^^^^^^^^

Config for supervisor daemon configuration (log and pid file paths) is in *config/supervisord.conf*.

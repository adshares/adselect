Welcome to AdSelect's documentation!
====================================

What is AdSelect
----------------

AdSelect is one the nodes in Adshares network. Its' role is to select ads to display for the Publisher. Banners are chosen based on previous payments and Publisher's ad policy.

Deployment
==========

Installation
------------
Full installation instructions can be found in `README.md <https://github.com/adshares/adselect/blob/master/README.md>`_. AdSelect is run within a Virtualenv. Dependencies are provided in requirements.txt and you can use pip to install them.

Make sure you set up the ``$ADSELECT_ROOT`` environment variable to point to the root directory of AdSelect - the directory containing the adselect package.

Configuration
-------------

Configuration is spread among 5 files:

    * adselect.db.const
    * adselect.iface.const
    * adselect.stats.const
    * config/log_config.json
    * config/supervisord.conf

AdSelect logging config
^^^^^^^^^^^^^^^^^^^^^

*config/log_config.json* contains Python logging configuration. You can learn more about it `here. <https://docs.python.org/2/library/logging.config.html>`_ The AdSelect daemon will look for this file in the ``$ADSELECT_ROOT/aduser/config`` directory, where ``$ADSELECT_ROOT`` is an environmental variable.

AdSelect database configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

*adselect.db.const* is a python file containing configuration for the MongoDB.

AdSelect interface configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

*adselect.iface.const* is a python file containing configuration for the JSON-RPC interface.

AdSelect banner selection configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

*adselect.stats.const* is a python file containing configuration for the selection calculations.

Supervisor config
^^^^^^^^^^^^^^^^^

Config for supervisor daemon configuration (log and pid file paths) is in *config/supervisord.conf*.

Logging
-------

Logging config for the Python app can be found in the *config/log_config.json* file. By default, it's captured by supervisor to ``$ADSELECT_ROOT/log/adselect.log``. Other logs (MongoDB, supervisord) can also be found in the same directory.

Architecture
------------
AdSelect is a Twisted app, backed by MongoDB and communicating with the network by JSON-RPC.

Python stack looks like this:

* Twisted for the core network communication and asynchronous event handling
* txmongo for asynchronous MongoDB communication
* fastjsonrpc for JSON-RPC protocol
* jsonobject for easy JSON-Python object mapping
* supervisor for running it as a daemon


Development
===========

Extending functionality
-----------------------

All the banner selection logic happens in :py:mod:`adselect.stats module`, so if you want to change the algorithms, you'll need to rewrite that part of the code. Some configuration is possible through the config files.

The calculations are run periodically. The main function for each run is the :py:func:`adselect.stats.recalculate_stats`. Calculation functions can be found in :py:mod:`adselect.stats.utils` and :py:mod:`adselect.stats.tasks`.


Testing
-------

For testing you'll need additional libraries (mock and mongomock). Tests can be run using Twisted Trial.

    ``trial tests``

To test with a live MongoDB instance, run the tests without the mongomock library.

    ``trial tests --without mongomock``

Packages
--------

.. toctree::
   :maxdepth: 1

   adselect
   adselect.contrib
   adselect.db
   adselect.iface
   adselect.stats

Indices and tables
------------------

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`

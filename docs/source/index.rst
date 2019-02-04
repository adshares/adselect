Welcome to AdSelect's documentation!
====================================

What is AdSelect
----------------

AdSelect is one the nodes in Adshares network. Its' role is to select ads to display for the Publisher. Banners are chosen based on previous payments and Publisher's ad policy.

Deployment
==========

Installation
------------

``git clone https://github.com/adshares/adselect.git``

``pipenv install``

or

``pipenv install --dev`` for development

Architecture
------------
AdSelect is a Twisted app, backed by MongoDB and communicating with the network by JSON-RPC.

Python stack looks like this:

* Twisted for the core network communication and asynchronous event handling
* txmongo for asynchronous MongoDB communication
* fastjsonrpc for JSON-RPC protocol
* jsonobject for easy JSON-Python object mapping

Development
===========

Extending functionality
-----------------------

All the banner selection logic happens in :py:mod:`adselect.stats module`, so if you want to change the algorithms, you'll need to rewrite that part of the code. Some configuration is possible through the config files.

The calculations are run periodically. The main function for each run is the :py:func:`adselect.stats.recalculate_stats`. Calculation functions can be found in :py:mod:`adselect.stats.utils` and :py:mod:`adselect.stats.tasks`.


Packages
--------

.. toctree::
   :maxdepth: 6

   how_it_works
   api
   reference
   testing
   config
   deploy
   contributing

Indices and tables
------------------

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`

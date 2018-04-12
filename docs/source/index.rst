Welcome to AdSelect's documentation!
====================================

What is AdSelect
----------------

AdSelect is one the nodes in Adshares network. Its' role is to select ads to display for the Publisher. Banners are chosen based on previous payments and Publisher's ad policy.

Architecture
------------
AdSelect is a Twisted app, backed by MongoDB and communicating with the network by JSON-RPC.

Python stack looks like this:

* Twisted for the core network communication and asynchronous event handling
* txmongo for asynchronous MongoDB communication
* fastjsonrpc for JSON-RPC protocol
* jsonobject for easy JSON-Python object mapping
* supervisor for running it as a daemon

Data stored in the database are organised in collections

* Campaigns
* Banners
* Impression counts (per publisher per banner size)
* Keyword payments (per publisher per banner)
* Scores

Calls and events

Packages
--------

.. toctree::
   :maxdepth: 1

   adselect
   adselect.contrib
   adselect.db
   adselect.iface
   adselect.stats
   Adshares.net <http://adshares.net/>

Indices and tables
------------------

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`

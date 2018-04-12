.. AdSelect documentation master file, created by
   sphinx-quickstart on Wed Apr 11 12:16:51 2018.
   You can adapt this file completely to your liking, but it should at least
   contain the root `toctree` directive.

.. toctree::
   :maxdepth: 1

   adselect.contrib
   adselect.db
   adselect.iface
   adselect.stats

Welcome to AdSelect's documentation!
====================================

What is AdSelect
----------------

AdSelect is one the nodes in Adshares network. It's role is to select ads to display for the Publisher. Banners are chosen based on previous payments and Publisher's ad policy.

Architecture
------------
AdSelect is a Twisted app, backed by MongoDB and communicating using JSONRPC.

Python stack looks like this:

* Twisted for the core network communication and asynchronous event handling
* txmongo for asynchronous MongoDB communication
* fastjsonrpc for JSONRPC protocol
* jsonobject for easy JSON-Python object mapping
* supervisor for running it as a daemon

Data stored in the database are organised in collections

* Campaigns
* Banners
* Impression counts (per publisher per banner size)
* Keyword payments (per publisher per banner)
* Something else

Calls and events


Indices and tables
------------------

* :ref:`genindex`
* :ref:`modindex`
* :ref:`search`

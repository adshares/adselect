Contributing
============

Code
----

The code is on github: https://github.com/adshares/adselect

Documentation
-------------

The documentation can be build using Sphinx with the following extensions:

* sphinx-rtd-theme (html theme)
* sphinxcontrib-httpdomain (api documentation)
* sphinx-jsondomain (fork by boolangery) (api/json objects documentation)

**Installation**

``pipenv install --dev``

**Building**

``pipenv run build_docs``

When documenting the API, you should first update the JSON objects in `adselect.iface.proto`. You can then use Sphinx generated documentation to paste response examples into API methods documentation.

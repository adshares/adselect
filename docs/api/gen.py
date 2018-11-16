import inspect

import yaml
from jsonobject import *
from jsonobject.properties import JsonProperty

from adselect.iface import protocol

clsmembers = inspect.getmembers(protocol, inspect.isclass)
apidocs = {}
for cls_name, obj in clsmembers:
    apidocs[cls_name] = {'properties': {}, 'type': 'object'}
    properties = inspect.getmembers(obj,
                                    lambda a: not(inspect.isroutine(a)))
    for p_name, prop in properties:
        if not isinstance(prop, JsonProperty):
            continue

        property_description = {}
        if isinstance(prop, StringProperty):
            property_description = {'type': 'string'}
        elif isinstance(prop, IntegerProperty):
            property_description = {'type': 'integer'}
        elif isinstance(prop, FloatProperty):
            property_description = {'type': 'number'}
        elif isinstance(prop, DictProperty):
            property_description = {'type': 'object'}
        elif isinstance(prop, ListProperty):
            property_description = {'type': 'array',
                                    'items': {'type': 'reference',
                                              'reference': prop.item_wrapper._item_type_deferred.__name__}}
        elif isinstance(prop, ObjectProperty):
            property_description = {'type': 'reference',
                                    'reference': prop._item_type_deferred.__name__}

        apidocs[cls_name]['properties'][p_name] = property_description

print yaml.dump(apidocs, default_flow_style=False)

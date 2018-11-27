class Filter(object):
    """
    Filter base class for filtering keywords.
    """
    NAME = None
    NESTED_FILTERS = False

    def __init__(self, filter_arg):
        self.filter_arg = filter_arg

    def is_valid(self, value):
        """Validate filter (not implemented)"""
        raise NotImplementedError()


class AndFilter(Filter):
    """
    .. code-block:: json

        {
         "type": "and",
         "args": [
                  {
                      "type": "=",
                      "args": "music",
                      "keywords": "interest"
                  },
                  {
                      "type": "<",
                      "args": 18,
                      "keywords": "age"
                  }
                 ]
        }

    """
    NAME = 'and'
    NESTED_FILTERS = True

    def is_valid(self, value):
        """
        Validate filter

        :return: True, if filter conditions are true.
        """
        for filter_obj in self.filter_arg:
            if not filter_obj.is_valid(value):
                return False
        return True


class OrFilter(Filter):
    """
    .. code-block:: json

        {
         "type": "or",
         "args": [
                  {
                      "type": "=",
                      "args": "music",
                      "keywords": "interest"
                  },
                  {
                      "type": "=",
                      "args": "art",
                      "keywords": "interest"
                  }
                 ]
        }

    """
    NAME = "or"
    NESTED_FILTERS = True

    def is_valid(self, value):
        """
        Validate filter

        :return: True, if filter conditions are true.
        """
        for filter_obj in self.filter_arg:
            if filter_obj.is_valid(value):
                return True
        return False


class EqualFilter(Filter):
    """
    .. code-block:: json

        {
          "type": "=",
          "args": "music",
          "keywords": "interest"
        }

    """
    NAME = '='

    def is_valid(self, value):
        return self.filter_arg == value


class GreaterEqualFilter(Filter):
    """
    .. code-block:: json

        {
          "type": ">=",
          "args": 18,
          "keywords": "age"
        }

    """
    NAME = ">="

    def is_valid(self, value):
        return value >= self.filter_arg


class LessEqualFilter(Filter):
    """
    .. code-block:: json

        {
          "type": "<=",
          "args": 17,
          "keywords": "age"
        }

    """
    NAME = "<="

    def is_valid(self, value):
        return value <= self.filter_arg


class LessFilter(Filter):
    """
    .. code-block:: json

        {
          "type": "<",
          "args": 18,
          "keywords": "age"
        }

    """
    NAME = '<'

    def is_valid(self, value):
        return value < self.filter_arg


class GreaterFilter(Filter):
    """
    .. code-block:: json

        {
          "type": ">",
          "args": 17,
          "keywords": "age"
        }

    """
    NAME = '>'

    def is_valid(self, value):
        return value > self.filter_arg


REGISTERED_FILTERS = [
    AndFilter,
    OrFilter,
    EqualFilter,
    GreaterEqualFilter,
    LessEqualFilter,
    LessFilter,
    GreaterFilter
]
FILTERS_NAMES_DICT = dict([(cls.NAME, cls) for cls in REGISTERED_FILTERS])


def json2filter(json_data):
    """
    Convert nested json type filter to object filter. See filter docstrings for json examples.

    :param json_data: JSON data containing nested filter.
    :return: Filter object
    """
    filter_type = json_data.get('type')
    if filter_type not in FILTERS_NAMES_DICT:
        return

    args = json_data.get('args')
    if args is None:
        return

    filter_class = FILTERS_NAMES_DICT[filter_type]
    if filter_class.NESTED_FILTERS:
        args = [json2filter(arg) for arg in args]
    return filter_class(args)

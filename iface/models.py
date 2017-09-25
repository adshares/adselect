import jsonobject


class FilterObject(jsonobject.JsonObject):
    type = jsonobject.StringProperty()
    args = jsonobject.StringProperty()


class KeywordFilterObject(jsonobject.JsonObject):
    keyword = jsonobject.StringProperty()
    filter = jsonobject.ObjectProperty(FilterObject)


class RequireExcludeListObject(jsonobject.JsonObject):
    require = jsonobject.ListProperty(KeywordFilterObject)
    exclude = jsonobject.ListProperty(KeywordFilterObject)


class BannerObject(jsonobject.JsonObject):
    banner_id = jsonobject.StringProperty()
    banner_size = jsonobject.StringProperty()
    keywords = jsonobject.JsonDict()


class CamapaignObject(jsonobject.JsonObject):
    campaign_id = jsonobject.StringProperty()
    time_start = jsonobject.IntegerProperty()
    time_end = jsonobject.IntegerProperty()
    filters = jsonobject.ListProperty(RequireExcludeListObject)
    keywords = jsonobject.JsonDict()
    banners = jsonobject.ListProperty()


class ImpressionObject(jsonobject.JsonObject):
    banner_id = jsonobject.StringProperty()
    keywords = jsonobject.JsonDict()
    publisher_id = jsonobject.StringProperty()



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
    keywords = jsonobject.DictProperty()


class CamapaignObject(jsonobject.JsonObject):
    campaign_id = jsonobject.StringProperty()
    time_start = jsonobject.IntegerProperty()
    time_end = jsonobject.IntegerProperty()
    filters = jsonobject.ListProperty(RequireExcludeListObject)
    keywords = jsonobject.DictProperty()
    banners = jsonobject.ListProperty()


class ImpressionObject(jsonobject.JsonObject):
    banner_id = jsonobject.StringProperty()
    keywords = jsonobject.DictProperty()
    publisher_id = jsonobject.StringProperty()


class SelectBannerRequest(jsonobject.JsonObject):
    request_id = jsonobject.IntegerProperty()
    publisher_id = jsonobject.IntegerProperty()
    user_id = jsonobject.StringProperty()
    banner_size = jsonobject.StringProperty()
    keywords = jsonobject.DictProperty()

class SelectBannerResponse(jsonobject.JsonObject):
    request_id = jsonobject.IntegerProperty()
    banner_id = jsonobject.StringProperty()

import jsonobject


class KeywordFilterObject(jsonobject.JsonObject):
    keyword = jsonobject.StringProperty()
    filter = jsonobject.DictProperty()


class RequireExcludeListObject(jsonobject.JsonObject):
    require = jsonobject.ListProperty(KeywordFilterObject)
    exclude = jsonobject.ListProperty(KeywordFilterObject)


class BannerObject(jsonobject.JsonObject):
    banner_id = jsonobject.StringProperty()
    banner_size = jsonobject.StringProperty()
    keywords = jsonobject.DictProperty()
    campaign_id = jsonobject.StringProperty()


class CamapaignObject(jsonobject.JsonObject):
    campaign_id = jsonobject.StringProperty()
    time_start = jsonobject.IntegerProperty()
    time_end = jsonobject.IntegerProperty()
    filters = jsonobject.ObjectProperty(RequireExcludeListObject)
    keywords = jsonobject.DictProperty()
    banners = jsonobject.ListProperty(BannerObject)


class ImpressionObject(jsonobject.JsonObject):
    banner_id = jsonobject.StringProperty()
    keywords = jsonobject.DictProperty()
    publisher_id = jsonobject.StringProperty()
    user_id = jsonobject.StringProperty()
    paid_amount = jsonobject.FloatProperty()


class SelectBannerRequest(jsonobject.JsonObject):
    request_id = jsonobject.IntegerProperty()
    publisher_id = jsonobject.IntegerProperty()
    user_id = jsonobject.StringProperty()
    banner_size = jsonobject.StringProperty()
    keywords = jsonobject.DictProperty()
    banner_filters = jsonobject.ObjectProperty(RequireExcludeListObject)


class SelectBannerResponse(jsonobject.JsonObject):
    request_id = jsonobject.IntegerProperty()
    banner_id = jsonobject.StringProperty()

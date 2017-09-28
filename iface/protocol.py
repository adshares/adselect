import jsonobject

class SelectBannerRequest(jsonobject.JsonObject):
    request_id = jsonobject.IntegerProperty()
    publisher_id = jsonobject.IntegerProperty()
    user_id = jsonobject.StringProperty()
    banner_size = jsonobject.StringProperty()
    keywords = jsonobject.DictProperty()


class SelectBannerResponse(jsonobject.JsonObject):
    request_id = jsonobject.IntegerProperty()
    banner_id = jsonobject.StringProperty()
import jsonobject


class KeywordFilterObject(jsonobject.JsonObject):

    keyword = jsonobject.StringProperty()
    """Keyword (String)"""

    filter = jsonobject.DictProperty()
    """Filter (Dictionary)"""


class RequireExcludeListObject(jsonobject.JsonObject):

    require = jsonobject.ListProperty(KeywordFilterObject)
    """List of required keywords (`KeywordFilterObject`)"""

    exclude = jsonobject.ListProperty(KeywordFilterObject)
    """List of excluded keywords (`KeywordFilterObject`)"""


class BannerObject(jsonobject.JsonObject):

    banner_id = jsonobject.StringProperty()
    """Main banner identifier (String)."""

    banner_size = jsonobject.StringProperty()
    """Banner size, in pixels, width x height (String)."""

    keywords = jsonobject.DictProperty()
    """Keywords (Dictionary of Strings)."""

    campaign_id = jsonobject.StringProperty()
    """Campaign identifier (String)."""


class CampaignObject(jsonobject.JsonObject):

    campaign_id = jsonobject.StringProperty()
    """Main campaign identifier (String)."""

    time_start = jsonobject.IntegerProperty()
    """Start epoch time (Integer)."""

    time_end = jsonobject.IntegerProperty()
    """End epoch time (Integer)."""

    filters = jsonobject.ObjectProperty(RequireExcludeListObject)
    """Required and Excluded keywords (`RequireExcludeListObject`)"""

    keywords = jsonobject.DictProperty()
    """Keywords (Dictionary of Strings)."""

    banners = jsonobject.ListProperty(BannerObject)
    """List of banners (List of `BannerObject`)"""


class ImpressionObject(jsonobject.JsonObject):

    banner_id = jsonobject.StringProperty()
    """Main banner identifier (String)"""

    keywords = jsonobject.DictProperty()
    """Keywords (Dictionary of Strings)"""

    publisher_id = jsonobject.StringProperty()
    """Publisher identifier (String)"""

    user_id = jsonobject.StringProperty()
    """User identifier (String)"""

    paid_amount = jsonobject.FloatProperty()
    """Payment for the impression (Float)"""


class SelectBannerRequest(jsonobject.JsonObject):

    request_id = jsonobject.IntegerProperty()
    """Request identifier (Integer)"""

    publisher_id = jsonobject.IntegerProperty()
    """Publisher identifier (Integer)"""

    user_id = jsonobject.StringProperty()
    """User identifier (String)"""

    banner_size = jsonobject.StringProperty()
    """Banner size, in pixels, width x height (String)."""

    keywords = jsonobject.DictProperty()
    """Keywords (Dictionary of Strings)"""

    banner_filters = jsonobject.ObjectProperty(RequireExcludeListObject)
    """Banner filters (`RequireExcludeListObject`)"""


class SelectBannerResponse(jsonobject.JsonObject):

    request_id = jsonobject.IntegerProperty()
    """Request identifier (Integer)"""

    banner_id = jsonobject.StringProperty()
    """Main banner identifier (String)"""

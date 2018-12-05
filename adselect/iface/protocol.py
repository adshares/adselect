import jsonobject


class RequireExcludeListObject(jsonobject.JsonObject):
    """
    .. json:object:: RequireExcludeListObject
       :showexample:

       :property [KeywordFilterObject] require: List of required keywords (`KeywordFilterObject`)
       :property [KeywordFilterObject] exclude: List of excluded keywords (`KeywordFilterObject`)

    """

    require = jsonobject.ListProperty(str)
    """List of required keywords (string)"""

    exclude = jsonobject.ListProperty(str)
    """List of excluded keywords (`KeywordFilterObject`)"""


class BannerObject(jsonobject.JsonObject):
    """
    .. json:object:: BannerObject
       :showexample:

       :property string banner_id: Unique banner identifier
       :property string campaign_id: Unique campaign identifier
       :propexample campaign_id: BXfmBKBdsQdDOdNbCtxd
       :property JSONObject keywords: Key-value map of keywords
       :property string banner_size: Banner size, eg. 100x400
       :propexample banner_size: 100x400

    """
    banner_id = jsonobject.StringProperty()
    """Main banner identifier (String)."""

    banner_size = jsonobject.StringProperty()
    """Banner size, in pixels, width x height (String)."""

    keywords = jsonobject.DictProperty()
    """Keywords (Dictionary of Strings)."""

    campaign_id = jsonobject.StringProperty()
    """Campaign identifier (String)."""


class CampaignObject(jsonobject.JsonObject):
    """
    .. json:object:: CampaignObject
       :showexample:

       :property string campaign_id: Unique campaign identifier
       :propexample campaign_id: BXfmBKBdsQdDOdNbCtxd
       :property integer time_start: Campaign start time (epoch time, in seconds)
       :propexample time_start: 1543326642
       :property integer time_end: Campaign end time (epoch time, in seconds)
       :propexample time_end: 1643326642
       :property JSONObject keywords: Key-value map of keywords
       :property JSONObject filters: RequireExcludeListObject
       :property [BannerObject] banners: List of banner objects

    """
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
    """
    .. json:object:: ImpressionObject
       :showexample:

       :property string banner_id: Main banner identifier (String)
       :property string publisher_id: Publisher identifier (String)
       :property string user_id: User identifier (String)
       :property float paid_amount: Payment for the impression (Float)
       :property JSONObject keywords: Keywords associated with the impression

    """
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
    """
    .. json:object:: SelectBannerRequest
       :showexample:

       :property integer request_id: Request id
       :property string publisher_id: Publisher id
       :property string user_id: User identifier (String)
       :property string banner_size: Banner size, eg. 100x400
       :propexample banner_size: 100x400
       :property JSONObject keywords: Keywords associated with the impression
       :property RequireExcludeListObject banner_filters: `RequireExcludeListObject`

    """
    request_id = jsonobject.IntegerProperty()
    """Request identifier (Integer)"""

    publisher_id = jsonobject.StringProperty()
    """Publisher identifier (String)"""

    user_id = jsonobject.StringProperty()
    """User identifier (String)"""

    banner_size = jsonobject.StringProperty()
    """Banner size, in pixels, width x height (String)."""

    keywords = jsonobject.DictProperty()
    """Keywords (Dictionary of Strings)"""

    banner_filters = jsonobject.ObjectProperty(RequireExcludeListObject)
    """Banner filters (`RequireExcludeListObject`)"""


class SelectBannerResponse(jsonobject.JsonObject):
    """
    .. json:object:: SelectBannerResponse
       :showexample:

       :property string banner_id: Unique banner identifier
       :property integer request_id: Request identifier
    """
    request_id = jsonobject.IntegerProperty()
    """Request identifier (Integer)"""

    banner_id = jsonobject.StringProperty()
    """Main banner identifier (String)"""

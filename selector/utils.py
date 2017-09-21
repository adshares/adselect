"""
    select_banner function should work as follow:
    1) Select banners which are paid a lot.
    2) Some percent of selected banners should be new banners without payments stats
    3) The same user shoudn't take the same banners every time.

"""

def select_banner(request_data):
    """
        List of requests:
         [
            {
                'id':'request_id'
                'filters':{
                    'required':[{
                        'keyword':'banner_keyword',
                        'filter':'banner_filter'
                    },],
                    'excluded':[{
                        'keyword':'banner_keyword',
                        'filter':'banner_filter'
                    },]
                },
                'userid':'',
                'keywords':impression_keywords
            },
        ]

        Should return list of banners to display
        [
            {
                'request_id':,
                'banner_id':
            },
        ]
    """
    return []
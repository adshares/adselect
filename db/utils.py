def add_campaigns(campaigns_list):
    """
        List of campaign data
        {
            'id':'campaign_id',
            'filters':{
                'required': [{
                    'keyword':'impression_keyword',
                    'filter':'impression_filter'
                }],
                'excluded':[{
                    'keyword':'impression_keyword',
                    'filter':'impression_filter'
                }],
            }
            banners:[
                {
                    'id':'banner_id',
                    'keywords':banner_keywords_dict,
                },
            ]
        }
    """

def update_campaigns(campaigns_list):
    pass


def delete_campaigns(campaigns_ids_list):
    """
        campaigns_ids_list list of id to delete
    """


def add_impressions(impression_list):
    """
        List of impressions with payments
        {
            'id':'banner_id',
            'keywords':'impression_keywords_dict',
            'amount':'paid_amount'
            'userid':''
        }
    """
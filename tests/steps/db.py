from behave import *

from adselect.db import utils


def write_to_db(table, update_function):
    """
    Convert behave table format to database friendly (dictionary) data

    :param table: Behave table.
    :param update_function: Function to use to save to database.
    :return:
    """
    for row in table:
        doc = {}
        for attr in row.headings:
            doc[attr] = row[attr]

        update_function(doc)


@given('Campaigns')
def step_impl(context):
    write_to_db(context.table, utils.update_campaign)


@given('Banners')
def step_impl(context):
    write_to_db(context.table, utils.update_banner)


@given('Events')
def step_impl(context):
    write_to_db(context.table, utils.update_event)

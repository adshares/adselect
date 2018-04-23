
from twisted.trial import unittest

from adselect.contrib import utils


class ContribTestCase(unittest.TestCase):

    def test_get_timestamp(self):
        timestamp = utils.get_timestamp()

    def test_merge(self):
        iterables = [[(6, 'banner1'), (2, 'banner2'), (3, 'banner3')],
                     [(1, 'banner1'), (8, 'banner2'), (3, 'banner3')]]
        for t in utils.merge(*iterables):
            print t


iterables = [[(6, 'banner1'), (2, 'banner2'), (3, 'banner3')],
            [(1, 'banner1'), (8, 'banner2'), (3, 'banner3')]]

for t in utils.merge(*iterables):
    print t

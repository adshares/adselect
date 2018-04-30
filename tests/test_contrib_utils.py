import random
from twisted.trial import unittest

from adselect.contrib import utils as contrib_utils


class TestContribuUtils(unittest.TestCase):

    def test_get_timestamp(self):
        ts = contrib_utils.get_timestamp()
        self.assertIs(type(ts), int)

    def test_reverse_insort(self):

        rev_sorted_list = [x for x in reversed(range(10))]

        with self.assertRaises(ValueError):
            contrib_utils.reverse_insort(rev_sorted_list, 1, -1)

        contrib_utils.reverse_insort(rev_sorted_list, 1, hi=None)
        self.assertEqual(len(rev_sorted_list), 11)

        # Check if order is ok
        for i in xrange(len(rev_sorted_list) - 1):
            self.assertGreaterEqual(rev_sorted_list[i], rev_sorted_list[i+1])

    def test_merge(self):

        iterable = []
        for count in xrange(4):

            it = [(x, chr(97 + count)) for x in range(10)]
            random.shuffle(it)
            iterable.append(it)

        # StopIterator pass
        iterable.append([])
        generated = [x for x in contrib_utils.merge(*iterable)]
        self.assertIsNotNone(generated)


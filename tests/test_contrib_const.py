from unittest import TestCase

from adselect.contrib import const


class TestSetConst(TestCase):

    def test_const(self):

        self.assertIsNot(const.LOG_LEVEL, None)
        self.assertIs(const.LOG_CONFIG_JSON_FILE, None)

from unittest import TestCase

from adselect.iface.utils import validate_require_keywords


class TestValidate_require_keywords(TestCase):
    def test_validate_require_keywords(self):
        passed = validate_require_keywords({"require": [], "exclude": []}, [])
        self.assertTrue(passed)
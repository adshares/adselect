from unittest import TestCase

from adselect.iface import server as iface_server


class TestConfigureIfaceServer(TestCase):

    def test_configure_iface(self):
        self.reactor = iface_server.configure_iface()
        self.assertIsNotNone(self.reactor)
        self.reactor.stopListening()

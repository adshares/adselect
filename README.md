<p align="center">
    <a href="https://adshares.net/" title="Adshares sp. z o.o." target="_blank">
        <img src="https://adshares.net/logos/ads.svg" alt="Adshares" width="100" height="100">
    </a>
</p>
<h3 align="center"><small>Adshares / AdSelect</small></h3>
<p align="center">
    <a href="https://travis-ci.org/adshares/adselect" title="Build Status" target="_blank">
        <img src="https://travis-ci.org/adshares/adselect.svg?branch=master" alt="Build Status">
    </a>
    <a href="https://sonarcloud.io/dashboard?id=adshares-adselect" title="Code Quality" target="_blank">
        <img src="https://sonarcloud.io/api/project_badges/measure?project=adshares-adselect&metric=alert_status" alt="Code Quality">
    </a>
</p>

AdSelect is a back-end service for ad selection.
It accepts requests from [AdServer](https://github.com/adshares/adserver) internally.



## Quick start

### Development

Start elasticsearch (docker example)
```shell
docker pull docker.elastic.co/elasticsearch/elasticsearch:7.14.0
docker network create elastic
docker run --name es01 --net elastic -p 9200:9200 -p 9300:9300 -e discovery.type=single-node \
  -it docker.elastic.co/elasticsearch/elasticsearch:7.14.0
```

Configure and start server
```shell
git clone https://github.com/adshares/adselect.git
cd adselect
composer install
composer dump-env dev
vi .env.local.php
composer dev
```

## Related projects
 
- [AdServer](https://github.com/adshares/adserver)
- [AdUser](https://github.com/adshares/aduser)
- [AdPay](https://github.com/adshares/adpay)
- [AdPanel](https://github.com/adshares/adpanel)
- [ADS](https://github.com/adshares/ads)
- [INSTALLER](https://github.com/adshares/installer)

## License

This work is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This work is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
[GNU General Public License](LICENSE) for more details.

You should have received a copy of the License along with this work.
If not, see <https://www.gnu.org/licenses/gpl.html>.

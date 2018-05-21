# adselect
[![Build Status](https://travis-ci.org/adshares/adselect.svg?branch=master)](https://travis-ci.org/adshares/adselect)
[![Build Status](https://sonarcloud.io/api/project_badges/measure?project=adshares-adselect&metric=alert_status)](https://sonarcloud.io/dashboard?id=adshares-adselect)
## Build
adselect is fully implemented in python.

### Dependencies

All dependenies are listed in requirements.txt file.

#### Linux

Exmaple for Debian based systems:
```
$ sudo apt-get install python-virtualenv mongodb
```

Create virtualenv environment for adselect.
```
$ cd ~
$ virtualenv adselect
$ source ~/adselect/bin/activate

$ export VIRTUALENV_ROOT=$HOME/adselect
$ export PYTHONPATH=$HOME/adselect:$PYTHONPATH
```

Create folder for MONGO database.
```
$ mkdir -p ~/adselect/db/mongo
```


Create folders for supervisor.
```
$ mkdir -p ~/adselect/log
$ mkdir -p ~/adselect/run/supervisor ~/adselect/run/adselect ~/adselect/run/mongo
```

Download source code and install dependencies.
```
$ git clone https://github.com/adshares/adselect.git ~/adselect/adselect
$ pip install -r ~/adselect/adselect/requirements.txt
```

Run adselect daemon.
```
$ supervisord -c ~/adselect/adselect/config/supervisord.conf
```

## Build
```
$ cd ~/adselect/adselect
$ trial iface stats
```
## TL;DR
```
apt-get install python-virtualenv mongodb
screen -S adselect
cd /home/adshares
virtualenv adselect
export VIRTUALENV_ROOT=$HOME/adselect
export PYTHONPATH=$HOME/adselect:$PYTHONPATH
source ./adselect/bin/activate
mkdir -p ./adselect/db/mongo
mkdir -p ./adselect/log
mkdir -p ./adselect/run/supervisor ./adselect/run/adselect ./adselect/run/mongo
git clone https://github.com/adshares/adselect.git ./adselect/adselect
pip install -r ./adselect/adselect/requirements.txt
supervisord -c ./adselect/adselect/config/supervisord.conf
```

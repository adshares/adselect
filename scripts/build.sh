#!/usr/bin/env bash

set -e

HERE=$(dirname $(readlink -f "$0"))
TOP=$(dirname ${HERE})
cd ${TOP}

if [[ -v GIT_CLONE ]]
then
  git --version || apt-get -qq -y install git

  git clone \
    --depth=1 \
    https://github.com/adshares/adselect.git \
    --branch ${BUILD_BRANCH:-master} \
    ${BUILD_PATH}/build

  cd ${BUILD_PATH}/build
fi

if [[ ${ADSELECT_APP_ENV} == 'dev' ]]; then
    pipenv install --dev pipenv
elif [[ ${ADSELECT_APP_ENV} == 'deploy' ]]; then
    pipenv install --deploy pipenv
else
    pip install pipenv
fi

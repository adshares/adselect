#!/usr/bin/env bash

## Shell cosmetics
bold=$(tput bold)
normal=$(tput sgr0)

env | sort

if [ ! -v TRAVIS ]; then
  # Checkout repo and change directory

  # Install git
  git --version || apt-get install -y git

  git clone \
    --depth=1 \
    https://github.com/adshares/adpanel.git \
    --branch ${ADSELECT_INSTALLATION_BRANCH} \
    ${ADSELECT_BUILD_PATH}/build

  cd ${ADSELECT_BUILD_PATH}/build
fi

envsubst < .env.dist | tee .env

if [ ${ADSELECT_APP_ENV} == 'dev' ]; then
    pipenv install --dev pipenv
elif [ ${ADSELECT_APP_ENV} == 'deploy' ]; then
    pipenv install --deploy pipenv
else
    pipenv install pipenv
fi

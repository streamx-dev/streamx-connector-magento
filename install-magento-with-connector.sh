#!/bin/bash
set -e # exit on 1st error

### PREREQUISITES:
# - streamx-ingestion-php repository cloned as sibling of streamx-connector-magento in your filesystem
# - Composer authentication to download Magento images is configured - see README.md

## Remove previous instance, if exists
if [ -d "magento" ]; then
    cd magento
    bin/removeall
    cd ..
    rm -rf magento
fi

### Prepare directory for magento installation
mkdir magento
cd magento

### Download magento docker images - see https://github.com/markshust/docker-magento
curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/template | bash
bin/download community 2.4.7-p3

### Remove unnecessary docker-magento's .git repo folder
rm -rf .git

### Increase innodb-buffer-pool-size to avoid warnings in logs
sed -i '' 's|--max_allowed_packet|--innodb-buffer-pool-size=512M --max_allowed_packet|g' compose.yaml

### To avoid conflicts with StreamX, replace known ports that are used by both StreamX and Magento by default:
# 8080: ingestion port in StreamX and phpmyadmin port in Magento
sed -i '' 's/8080:80/8090:80/g' compose.dev.yaml

### Enable gathering code coverage
echo -e "\nXDEBUG_MODE=coverage" >> env/phpfpm.env

### Install the magento docker machinery
bin/setup magento.test

### Install sample data
bin/magento sampledata:deploy

### Import php ingestion client for development
# Note: in future, when the client is made publicly available - we will be just using `composer require streamx/ingestion-client`.
# For now, manually copy the client's code to the project, to a path that is git ignored.
# This script assumes you have both projects checked out as siblings in filesystem.
cd ..
rm -rf streamx-ingestion-php
mkdir streamx-ingestion-php
cp -R ../streamx-ingestion-php/* streamx-ingestion-php

### Upload php ingestion client to Magento
# Note: in future, when the client is made publicly available - we will be just using `composer require streamx/ingestion-client`.
# For now, manually copy source code of the client to Magento
bash copy_client_to_magento.sh

### Upload StreamX Connector to Magento
# Note: in future, when the connector is made publicly available - we will be just using `composer require streamx/magento-connector`.
# For now, manually copy source code of the connector to Magento
bash copy_connector_to_magento.sh

### Install StreamX Client and Connector to Magento
# Point Magento to search for the client / connector source code in its directory
cd magento
bin/composer config repositories.streamx-client path app/code/StreamX/Client
bin/composer config repositories.streamx-connector path app/code/StreamX/Connector

# Add streamx client / connector to Magento's composer.json file
# Also turn off Two Factor Auth for development purposes and enable gathering code coverage
bin/composer require \
  "streamx/ingestion-client" \
  "streamx/magento-connector" \
  "markshust/magento2-module-disabletwofactorauth" \
  "ext-xdebug"

# Enable all modules
bin/magento module:enable --all
cd ../ && bash reload_magento_modules.sh
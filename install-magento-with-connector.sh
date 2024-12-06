#!/bin/bash
set -e # exit on 1st error

### PREREQUISITES:
# - You can access the https://github.com/streamx-dev/streamx-ingestion-php repository
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

### Upload StreamX Connector to Magento
# Note: in future, when the connector is made publicly available - we will be just using `composer require streamx/magento-connector`.
# For now, manually copy source code of the connector to Magento
cd ..
bash copy_connector_to_magento.sh

### Install StreamX Connector to Magento
# Point Magento to search for the connector source code in its directory
cd magento
bin/composer config repositories.streamx-client \
  path app/code/StreamX/Client

# Register streamx-ingestion-php repository (the module is required by the connector)
bin/composer config repositories.streamx-client \
  vcs https://github.com/streamx-dev/streamx-ingestion-php

# Add the connector to Magento's composer.json file (along with a module that turns off Two Factor Auth for development purposes and extension for gathering code coverage)
bin/composer require \
  "streamx/magento-connector" \
  "markshust/magento2-module-disabletwofactorauth" \
  "ext-xdebug"

# Enable all modules
bin/magento module:enable --all
cd ..
bash reload_magento_modules.sh
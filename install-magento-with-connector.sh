#!/bin/bash
set -e # exit on 1st error

### PREREQUISITES:
# - Composer authentication to download Magento images is configured - see README.md

## Remove previous instance, if exists
if [ -d "magento" ]; then
    cd magento
    bin/removeall || true # continue also if the magento instance wasn't yet installed
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
bash copy-connector-to-magento.sh

# For testing purposes, upload also the StreamX Connector Test Tools to Magento
bash copy-connector-test-tools-to-magento.sh

### Install StreamX Connector to Magento
cd magento

# Point Magento to search for the connector source code in its directory
bin/composer config repositories.streamx-connector \
  path app/code/StreamX/Connector

# For testing purposes, register also the connector test tools module
bin/composer config repositories.streamx-collector-test-tools \
  path app/code/StreamX/ConnectorTestTools

# Add the connector to Magento's composer.json file (along with a module that turns off Two Factor Auth for development purposes and extension for gathering code coverage)
bin/composer require \
  "streamx/magento-connector" \
  "streamx/magento-connector-test-tools" \
  "markshust/magento2-module-disabletwofactorauth" \
  "ext-xdebug"

# Enable all modules
bin/magento module:enable --all
cd ..
bash reload-magento-modules.sh
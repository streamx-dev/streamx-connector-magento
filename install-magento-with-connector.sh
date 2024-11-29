### PREREQUISITES:
# - streamx-ingestion-php repository cloned as sibling of streamx-connector-magento in your filesystem
# - Composer authentication to download Magento images is configured - see README.md

### FRESH RUN:
# If you already have a Magento instance configured - to remove everything, execute:
# cd magento
# bin/removeall
# cd ..
# rm -rf magento

### Prepare directory for magento installation
rm -rf magento
mkdir magento
cd magento

### Install magento docker images - see https://github.com/markshust/docker-magento
curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test community 2.4.7-p3

### Remove unnecessary docker-magento's .git repo folder
rm -rf .git

# Increase innodb-buffer-pool-size to avoid warnings in logs
sed -i '' 's|--max_allowed_packet|--innodb-buffer-pool-size=512M --max_allowed_packet|g' compose.yaml

# Turn off Two Factor Auth for development purposes
bin/composer require --dev markshust/magento2-module-disabletwofactorauth
bin/magento module:enable MarkShust_DisableTwoFactorAuth

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
bin/composer require "streamx/ingestion-client"
bin/composer require "streamx/magento-connector"

# Register the module in Magento's system and apply any database changes or setup scripts
bin/magento setup:upgrade

# Clear Magento's cache to ensure the changes are reflected
bin/magento cache:clean
bin/magento cache:flush

### To avoid conflicts with StreamX, replace known ports that are used by both StreamX and Magento by default:
# - 9200, 9300: used by opensearch in both places
sed -i '' 's/9200:9200/9201:9200/g' compose.yaml
sed -i '' 's/9300:9300/9301:9300/g' compose.yaml
sed -i '' 's/OPENSEARCH_PORT=9200/OPENSEARCH_PORT=9201/g' env/opensearch.env

# - 8080: ingestion port in StreamX and phpmyadmin port in the Magento
sed -i '' 's/8080:80/8090:80/g' compose.dev.yaml

### Restart Magento to load all changes
bin/stopall
bin/start
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
mkdir magento
cd magento

### Install magento docker images - see https://github.com/markshust/docker-magento
curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test 2.4.7 community

# Increase innodb-buffer-pool-size to avoid warnings in logs
sed -i '' 's|--max_allowed_packet|--innodb-buffer-pool-size=512M --max_allowed_packet|g' compose.yaml

### Remove unnecessary docker-magento's .git repo folder
rm -rf .git

### Install sample data
bin/magento sampledata:deploy
bin/magento setup:upgrade

### Import php ingestion client for development
# Note: in future, when the client is made publicly available - we will be just using `composer require streamx/ingestion-client`.
# For now, manually copy the client's code to the project, to a path that is git ignored.
# This script assumes you have both projects checked out as siblings in filesystem.
cd ..
mkdir streamx-ingestion-php
cp -R ../streamx-ingestion-php/* streamx-ingestion-php

### Upload php ingestion client to Magento
# Note: in future, when the client is made publicly available - we will be just using `composer require streamx/ingestion-client`.
# For now, manually copy source code of the client to Magento
rm -rf magento/src/app/code/StreamX/streamx-ingestion-php
mkdir -p magento/src/app/code/StreamX/Client
cp -R streamx-ingestion-php/ magento/src/app/code/StreamX/Client

### Install the StreamX Connector to Magento
# Point Magento to search for the client's source code in its directory
cd magento
bin/composer config repositories.streamx-client path app/code/StreamX/Client

# Add streamx ingestion client to magento's composer.json file
bin/composer require "streamx/ingestion-client"

# Magento requires custom code to be placed in its src/app/code directory. Copy the Connector code to magento
cd ..
rm -rf magento/src/app/code/StreamX/Connector
mkdir -p magento/src/app/code/StreamX/Connector
cp -R connector/src/* magento/src/app/code/StreamX/Connector

# Enable the StreamX Connector module
cd magento
bin/magento module:enable StreamX_Connector

# Register the module in Magento’s system and apply any database changes or setup scripts
bin/magento setup:upgrade

# Clear Magento’s cache to ensure the changes are reflected
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
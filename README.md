### The `magento` module was created using:

```bash
mkdir magento
cd magento

# Install magento docker images - see https://github.com/markshust/docker-magento
curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/onelinesetup | bash -s -- magento.test 2.4.7 community

# Verify magento.test hostname is added to /etc/hosts as alias to 127.0.0.1

# Remove unnecessary .git repo folder from magento directory:
rm -rf .git

# Install sample data
bin/magento sampledata:deploy
bin/magento setup:upgrade

# Magento web page
open https://magento.test

# Verify sample data is available by searching for the term "Bag"
open https://magento.test/catalogsearch/result/?q=bag # should display some items

# Magento admin page
open https://magento.test/admin

# Login using credentials from magento/env/magento.env file
# When asked to check your mail for 2FA - open mailcatcher to read and follow the instructions:
open http://localhost:1080

# If the Magento admin page displays warning about invalidated cache - perform the actions according to the displayed message

# If you want to reset the whole magento installation - first remove everything that was created by it, by executing:
bin/removeall

# How to start the already configured magento, if for example you stopped all its containers manually:
bin/start
```

### Install the StreamX Connector:
The connector requires PHP Ingestion Client for StreamX.
If the client is not yet available via composer - first go through the sections `Import php ingestion client for development`
and `Upload php ingestion client to Magento`. Then return here and follow the below steps.

Execute the following commands from root directory of the project:
```bash
# Add streamx ingestion client to magento's composer.json file by:
cd magento
bin/composer require "streamx/ingestion-client"

# Magento requires plugins to be placed in its src/app/code directory.
# Copy the plugin code to magento on each change (execute from project root directory):
rm -rf magento/src/app/code/StreamX/Connector
mkdir -p magento/src/app/code/StreamX/Connector
cp -R connector/src/* magento/src/app/code/StreamX/Connector

# Enable the Module
cd magento
bin/magento module:enable StreamX_Connector

# Register the module in Magento’s system and apply any database changes or setup scripts:
bin/magento setup:upgrade

# After installation, it’s a good practice to clear Magento’s cache to ensure the changes are reflected:
bin/magento cache:clean
bin/magento cache:flush

# Verify the Installation. The custom module should now be listed as enabled and ready to use in Magento:
bin/magento module:status
```

### Apply code updates of the StreamX Connector to Magento:
In most cases it's enough to copy (overwrite) the plugin files. No need to restart Magento

### Known ports that are used by both StreamX and Magento by default:
 - 9200, 9300: used by opensearch in both places
 - 8080: ingestion port in StreamX and phpmyadmin port in the Magento

If you decide to change ports in Magento - here's how to do it, before starting StreamX:
 - in magento/compose.yaml, replace:
   - "9200:9200" -> "9201:9200"
   - "9300:9300" -> "9301:9300"
 - in magento/env/opensearch.env, replace:
   - OPENSEARCH_PORT=9200 -> OPENSEARCH_PORT=9201
 - in magento/compose.dev.yaml, replace:
   - "8080:80" -> "8090:80"

Restart Magento (call the commands from project root directory):
```bash
cd magento
bin/stopall
bin/start
```

### Start StreamX
```bash
streamx run -f your-mesh-file.yaml
```

Note: in case of problems such as StreamX not starting or some of its containers suddenly die - consider reducing number of service containers in StreamX

### Setup communication between StreamX Connector (running on Magento server) and StreamX Rest Ingestion service
The connector plugin calls StreamX Rest Ingestion service endpoint to publish/unpublish collected data.
If you host both StreamX and Magento on Docker containers - perform the following action:

To enable the plugin to call the service by its hostname, add the `rest-ingestion` docker container to magento's network:
```bash
docker network ls | grep magento_default
# Take the network's id and execute the command:
docker network connect [magento-network-id] rest-ingestion
```

### Import php ingestion client for development
Note: in future, when the client is made publicly available - we will be just using `composer require streamx/ingestion-client`.

For now, manually copy the client's code to the project, to a path that is git ignored.
Assuming you have both projects checked out as siblings in filesystem, execute the command from project root directory:
```bash
mkdir streamx-ingestion-php
cp -R ../streamx-ingestion-php/* streamx-ingestion-php
```

### Upload php ingestion client to Magento
Note: in future, when the client is made publicly available - we will be just using `composer require streamx/ingestion-client`.

For now, run the commands from root directory of the project, to copy source code of the client to Magento:
```bash
rm -rf magento/src/app/code/StreamX/streamx-ingestion-php
mkdir -p magento/src/app/code/StreamX/Client
cp -R streamx-ingestion-php/ magento/src/app/code/StreamX/Client

# Point Magento to search for the client in the above directory:
cd magento
bin/composer config repositories.streamx-client path app/code/StreamX/Client
```

### Run tests with coverage
1. Install xdebug (with version that supports PHP 7.4):
```bash
pecl install xdebug-3.1.5
```

2. Configure xdebug mode:
```bash
export XDEBUG_MODE=coverage
```

3. Run tests with coverage and open results in web browser:
```bash
cd connector
./vendor/bin/phpunit --coverage-text --coverage-html target/coverage-report
open target/coverage-report/index.html
```
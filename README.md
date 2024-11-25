To setup Magento docker images along with StreamX Connector, execute the `install-magento-with-connector.sh` script.

Verify magento.test hostname is added to /etc/hosts as alias to 127.0.0.1

Verify the Installation. The StreamX Connector module should be listed as enabled and ready to use in Magento:
```bash
cd magento
bin/magento module:status
```

Magento web page:
https://magento.test

Verify sample data is available by searching for the term "Bag":
https://magento.test/catalogsearch/result/?q=bag

In case of errors searching, refresh search index:
```bash
cd magento
bin/magento indexer:status
bin/magento indexer:reindex catalogsearch_fulltext
```

Magento admin page:
https://magento.test/admin

Login using credentials from magento/env/magento.env file.
When asked to check your mail for 2FA - open mailcatcher to read and follow the instructions:
open http://localhost:1080

If the Magento admin page displays warning about invalidated cache - perform the actions according to the displayed message

To start the already configured magento, if for example you stopped all its containers manually:
```bash
cd magento
bin/start
```

When developing changes in the StreamX Connector - there's no need to restart Magento. Upload and apply changes to Magento using:
```bash
# cd to root directory of the project, and:
rm -rf magento/src/app/code/StreamX/Connector
mkdir -p magento/src/app/code/StreamX/Connector
cp -R connector/src/* magento/src/app/code/StreamX/Connector

cd magento
bin/magento cache:clean && bin/magento cache:flush && bin/magento setup:upgrade && bin/magento setup:di:compile
```

Restart Magento:
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
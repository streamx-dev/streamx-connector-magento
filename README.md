# Setup Composer authentication for downloading Magento docker images:

Steps to Obtain and Configure Magento Authentication Keys
1. Generate Authentication Keys
 - log in to the Magento Marketplace: https://marketplace.magento.com/customer/account/login
 - access Your Profile: Click on your account name in the top right corner and select My Profile
 - create Access Keys:
   - navigate to the Access Keys section under the Marketplace tab
   - click on Create a New Access Key
   - enter a name for the keys (e.g. "Magento Project") and click OK
   - note down the generated Public Key (used as the username) and Private Key (used as the password)
2. Verify Composer Installation

Ensure that Composer is installed on your system. If not, you can install it using one of the following methods:

- **Using Homebrew (macOS):**  
  Run the following command in your terminal:  
  ```bash
  brew install composer
  ```

  Otherwise, visit the official Composer installation guide for detailed instructions: https://getcomposer.org/

3. Configure auth.json
 - locate Composer home directory. You can find this by running:
   ```bash
   composer config --list --global | grep 'home'
   ```
 - create (or edit) the file `auth.json` in the directory
   - add your authentication keys in the following format:
     ```json
     {
        "http-basic": {
            "repo.magento.com": {
                "username": "<your_public_key>",
                "password": "<your_private_key>"
            }
        }
     }
     ```
 - replace <your_public_key> and <your_private_key> with the keys you generated earlier in step 1.

# Setup Magento docker images along with StreamX Connector
Execute the `install-magento-with-connector.sh` script.

Verify magento.test hostname is added to /etc/hosts as alias to 127.0.0.1

Verify the Installation. The StreamX Connector module should be listed as enabled and ready to use in Magento:
```bash
cd magento
bin/magento module:status
```

## Magento web page
https://magento.test:444

Verify sample data is available by searching for the term "Bag":
https://magento.test:444/catalogsearch/result/?q=bag

In case of errors searching, refresh search index:
```bash
cd magento
bin/magento indexer:status
bin/magento indexer:reindex catalogsearch_fulltext
```

## Magento admin page
https://magento.test:444/admin

Login using credentials from magento/env/magento.env file.

If the Magento admin page displays warning about invalidated cache - perform the actions according to the displayed message

## Enable StreamX Connector
 - Go to Magento Admin page
 - Click on `STORES` on the left panel
 - Click `Configuration` in the `Settings` area
 - Expand `STREAMX` section, click on `Indexer` item, then expand `General Settings` section on the right
 - Select `Yes` for the `Enable StreamX Connector` setting
 - Click the `Save Config` button

## Configure StreamX Connector
 - Go to Magento Admin page
 - Click on `STORES` on the left panel
 - Click `Configuration` in the `Settings` area
 - Expand `STREAMX` section, click on `Indexer` item, then expand `StreamX Connector Settings` section on the right
 - Edit the settings you need, or leave the default values
 - Click the `Save Config` button

## Start the already configured magento
If for example you stopped all its containers manually, you can start them using:
```bash
cd magento
bin/start
```

## Applying changes
When developing changes in the StreamX Connector - there's no need to restart Magento. Upload and apply changes to Magento using:
```bash
# cd to root directory of the project, and:
bash copy-connector-to-magento-and-reload.sh
```

## Restart Magento:
```bash
cd magento
bin/stopall
bin/start
```

## Start StreamX
```bash
streamx run -f your-mesh-file.yaml
```
Note: in case of problems such as StreamX not starting or some of its containers suddenly die - consider reducing number of service containers in StreamX

## Setup communication between StreamX Connector (running on Magento server) and StreamX Rest Ingestion service
StreamX connector calls StreamX Rest Ingestion service endpoint to publish/unpublish collected data.
If you host both StreamX and Magento on Docker containers - perform the following action:

To enable the connector to call the service by its hostname, add the `rest-ingestion` docker container to magento's network:
```bash
bash add-rest-ingestion-to-magento-network.sh
```

## Where to find logs
When using `markshust/docker-magento`, logs written with `Psr\Log\LoggerInterface` are saved to:
`/var/www/html/var/log/system.log`

## Run tests with coverage
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
composer update # enough to execute this only once - will create the './vendor/bin/phpunit' directory
./vendor/bin/phpunit --coverage-text --coverage-html target/coverage-reports
open target/coverage-reports/index.html
```

## Some useful magento commands (when using markshust/docker-magento)
```bash
cd magento

# list all indexers
bin/magento indexer:status
# the indexers are also listed on Magento Admin's System > Tools > Index Management page.

# reindex a specified index
bin/magento indexer:reindex index_identifier

# reindex all indexers
bin/magento indexer:reindex

# change mode of an indexer
bin/magento indexer:set-mode schedule|realtime index_identifier

# you can also use dedicated streamx indexers commands, such as:
bin/magento streamx:reset # see class ResetStreamxIndexersCommand
bin/magento streamx:index streamx_product_indexer 1 123 # see PublishSingleEntityCommand
bin/magento streamx:reindex --all #see PublishAllEntitiesCommand

# review logs and errors of the Magento container (typically named magento-phpfpm-1)
cat /var/www/html/var/log/system.log
cat /var/www/html/var/log/exception.log

# execute all scheduled jobs - including StreamxIndexerMviewProcessor to process changelog tables for indexers in Update by Schedule mode:
bin/magento cron:run
cat /var/www/html/var/log/cron.log
```

## Some useful MySQL commands (when using markshust/docker-magento)
SQL db root user is:
 - login: `root`
 - password: `magento`

Turn on logging all SQL queries to log file:
```sql
SET GLOBAL general_log = 'ON';
SET GLOBAL log_output = 'FILE';
```

The queries are logged to the following directory and file:
```sql
SHOW VARIABLES LIKE 'datadir';
SHOW VARIABLES LIKE 'general_log_file';
```

Turn off when done:
```sql
SET GLOBAL general_log = 'OFF';
```

## Troubleshooting REST endpoints
 - make sure your endpoint relative path always starts with /V1/...
 - endpoints are mounted at base url https://magento.test:444/rest/all/V1/...
 - example endpoint that should be available out of the box: https://magento.test:444/rest/all/V1/directory/currency
 - review https://magento.test:444/rest/all/schema for any errors or additional information

## Measuring the Connector's code coverage on a running Magento PHP server
 - in PHP, coverage can be measured for a Http Request (all code executed by the code that handles the request is measured)
 - some of the test REST endpoints from `connector-test-tools` are enriched with measuring coverage measurement (you can add more)
 - coverage data (returned by `xdebug`) is originally an associative array, but the endpoints return it json-serialized to a string
 - integration tests generate coverage reports if you start them with `GENERATE_CODE_COVERAGE_REPORT=true` env variable added to your Run/Debug configuration for the tests
 - the coverage reports are generated to `target/coverage-reports` in the root directory of the project, with folder names corresponding to test names
 - open `index.html` of a report in your browser for a report in clickable / navigable form
 - Known issues:
   - in summary view, the reports display classes as 100% covered, but when you open any class, the coverage is correct (green areas mark the actual covered code)
 - TODO:
   - it should be possible to measure summary view, by dumping raw results from each test, and then calling `$codeCoverage->append` on all of them

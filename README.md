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
2. Configure auth.json
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
https://magento.test

Verify sample data is available by searching for the term "Bag":
https://magento.test/catalogsearch/result/?q=bag

In case of errors searching, refresh search index:
```bash
cd magento
bin/magento indexer:status
bin/magento indexer:reindex catalogsearch_fulltext
```

## Magento admin page
https://magento.test/admin

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
bash copy_connector_to_magento_and_reload.sh
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
cd connector
./vendor/bin/phpunit --coverage-text --coverage-html target/coverage-report
open target/coverage-report/index.html
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
```
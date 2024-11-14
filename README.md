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
```

### Install the StreamX Connector:
Execute the following commands from root directory of the project.

```bash
# Magento requires plugins to be placed in its src/app/code directory.
# Copy the plugin code to magento on each change:
rm -rf magento/src/app/code/StreamX/Connector
mkdir -p magento/src/app/code/StreamX/Connector
cp -R src/* magento/src/app/code/StreamX/Connector

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

### Apply code updates of the StreamX Connector in Magento:
In most cases it's enough to copy (overwrite) the plugin files. No need to restart Magento

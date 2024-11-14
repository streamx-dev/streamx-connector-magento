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

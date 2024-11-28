bash copy_connector_to_magento.sh

cd magento
bin/magento cache:clean
bin/magento cache:flush
bin/magento setup:upgrade
bin/magento setup:di:compile

cd ../
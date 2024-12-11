cd magento

bin/magento cache:clean
bin/magento cache:flush
bin/magento setup:upgrade
bin/magento setup:di:compile
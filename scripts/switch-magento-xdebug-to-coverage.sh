cd magento

bin/stop phpfpm
echo -e "\nXDEBUG_MODE=coverage" >> env/phpfpm.env
bin/start phpfpm

cd ../

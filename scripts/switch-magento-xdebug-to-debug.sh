cd magento

bin/stop phpfpm
echo -e "\nXDEBUG_MODE=debug" >> env/phpfpm.env
bin/start phpfpm

cd ../

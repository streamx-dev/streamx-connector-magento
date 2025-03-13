cd magento

bin/stop phpfpm
printf "\nXDEBUG_MODE=coverage" >> env/phpfpm.env
bin/start phpfpm

cd ../

cd magento

bin/stop phpfpm
printf "\nXDEBUG_MODE=debug" >> env/phpfpm.env
bin/start phpfpm

cd ../

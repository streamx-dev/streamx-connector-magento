rm -rf magento/src/app/code/StreamX/Client
mkdir -p magento/src/app/code/StreamX/Client
cp -R streamx-ingestion-php/ magento/src/app/code/StreamX/Client

# remove client files that magento doesn't need
rm -rf magento/src/app/code/StreamX/Client/vendor
rm -rf magento/src/app/code/StreamX/Client/target
rm -rf magento/src/app/code/StreamX/Client/tests
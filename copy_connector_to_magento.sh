rm -rf magento/src/app/code/StreamX/Connector
mkdir -p magento/src/app/code/StreamX/Connector
cp -R connector/ magento/src/app/code/StreamX/Connector

# remove connector files that magento doesn't need
rm -rf magento/src/app/code/StreamX/Connector/vendor
rm -rf magento/src/app/code/StreamX/Connector/src/core/test
rm -rf magento/src/app/code/StreamX/Connector/src/core/vendor
rm -rf magento/src/app/code/StreamX/Connector/src/catalog/test
rm -rf magento/src/app/code/StreamX/Connector/src/catalog/vendor

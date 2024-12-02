rm -rf magento/src/app/code/StreamX/Connector
mkdir -p magento/src/app/code/StreamX/Connector/src

cp -R connector/src magento/src/app/code/StreamX/Connector
cp -R connector/composer.json magento/src/app/code/StreamX/Connector

rm -rf magento/src/app/code/StreamX/Connector/src/core/test
rm -rf magento/src/app/code/StreamX/Connector/src/catalog/test
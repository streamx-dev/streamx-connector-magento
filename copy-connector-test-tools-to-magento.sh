rm -rf magento/src/app/code/StreamX/ConnectorTestTools
mkdir -p magento/src/app/code/StreamX/ConnectorTestTools

cp -R connector-test-tools/{Api,Impl,etc,composer.json,registration.php} magento/src/app/code/StreamX/ConnectorTestTools/

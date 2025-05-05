DEST_DIR=magento/src/app/code/StreamX/ConnectorTestEndpoints

rm -rf $DEST_DIR
mkdir -p $DEST_DIR

cp -R connector-test-endpoints/{Api,Impl,etc,composer.json,registration.php} $DEST_DIR/

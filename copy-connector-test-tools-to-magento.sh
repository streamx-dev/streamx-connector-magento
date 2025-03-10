DEST_DIR=magento/src/app/code/StreamX/ConnectorTestTools

rm -rf $DEST_DIR
mkdir -p $DEST_DIR

cp -R connector-test-tools/{Api,Impl,etc,composer.json,registration.php} $DEST_DIR/
mkdir $DEST_DIR/resources
cp test/resources/magento-products.csv $DEST_DIR/resources/

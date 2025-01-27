1. Install StreamX Connector in Magento
```bash
cd $MAGENTO_ROOT_DIR
bin/composer require streamx/magento-connector
```

Note: if the command asks you for a github access token - just press ENTER

2. Perform steps to enable the Connector's modules in Magento
```bash
cd $MAGENTO_ROOT_DIR
bin/magento module:enable StreamX_ConnectorCore
bin/magento module:enable StreamX_ConnectorCatalog
bin/magento cache:clean
bin/magento cache:flush
bin/magento setup:upgrade
bin/magento setup:di:compile
```

3. Start your StreamX instance (or verify it's already running)
```bash
streamx run -f path-to-your-mesh-file
```

You can find a basic mesh file at `src/catalog/test/resources/mesh.yaml`

4. Enable and configure StreamX Connector via the Magento Admin page
   - Click on `STORES` on the left panel
   - Click `Configuration` in the `Settings` area
   - Expand `STREAMX` section, click on `Connector` item, then expand `General Settings` section on the right
   - Select `Yes` for the `Enable StreamX Connector` setting
   - Expand `StreamX Connector Settings` section below
   - Edit the settings you need, or leave the default values
   - Click the `Save Config` button


5. Verify the Connector by indexing and publishing all categories to StreamX
```bash
cd $MAGENTO_ROOT_DIR
bin/magento indexer:reindex streamx_category_indexer
```

6. Once the command completes:
 - open your StreamX instance's Web Delivery Service endpoint in a web browser
 - verify the JSON output at a category URL
 - assuming you are using a local StreamX instance configured to run Web Delivery Service on port 8081, the URL is:
   - http://localhost:8081/cat:6 (where 6 is the Category ID)
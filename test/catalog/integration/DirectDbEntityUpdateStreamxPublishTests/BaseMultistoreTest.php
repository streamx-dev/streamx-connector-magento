<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * {@inheritdoc}
 *
 * Additional prerequisites to run this test: a second Store must be created and configured:
 *  - Login as Admin to Magento
 *  - Create second store: Stores -> Settings -> All Stores
 *    - Create Store with any name and code; use "Default Category" as Root Category
 *    - Create Store View for the newly created store (using any name and code); select Status as "Enabled"
 *  - Go to StreamX Connector settings, add the new store view to the list of Stores to reindex
 *  - Open Streamx Ingestion settings, and override settings in scope of the newly created store (view):
 *    - "pim_store_2:" as the product key prefix
 *    - "cat_store_2:" as the category key prefix
 */
abstract class BaseMultistoreTest extends BaseDirectDbEntityUpdateTest {

    protected const DEFAULT_STORE_ID = 0; // comes with markshust/docker-magento
    protected const STORE_1_ID = 1; // comes with markshust/docker-magento
    protected const STORE_2_ID = 3; // manually created according to instructions in the class comments
}
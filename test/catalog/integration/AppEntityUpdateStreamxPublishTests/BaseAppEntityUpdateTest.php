<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;

/**
 * @inheritdoc
 *
 * Base class for testing indexers in Update On Save mode.
 * These tests modify products/categories/attributes by simulating how Admin would perform them in the Magento's web interface.
 * Those modifications are performed by calling endpoints defined in connector-test-endpoints, using MagentoEndpointsCaller class.
 * The test endpoints are deployed to the test Magento instance, and are executed serverside.
 */
abstract class BaseAppEntityUpdateTest extends BaseStreamxConnectorPublishTest {
    const INDEXER_MODE = parent::UPDATE_ON_SAVE;
}
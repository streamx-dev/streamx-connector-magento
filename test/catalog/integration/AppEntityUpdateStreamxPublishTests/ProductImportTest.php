<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseProductImportTest;

/**
 * @inheritdoc
 */
class ProductImportTest extends BaseProductImportTest {
    const INDEXER_MODE = parent::UPDATE_ON_SAVE;
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseProductImportTest;

/**
 * @inheritdoc
 */
class ProductImportTest extends BaseProductImportTest {

    const INDEXER_MODE = parent::UPDATE_BY_SCHEDULE;

    protected function importProducts(string $csvContent, string $behavior): void {
        parent::importProducts($csvContent, $behavior);
        self::reindexMview();
    }
}
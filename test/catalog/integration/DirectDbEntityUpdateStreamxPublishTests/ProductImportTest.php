<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseProductImportTest;

/**
 * @inheritdoc
 * @UpdateByScheduleIndexerMode
 */
class ProductImportTest extends BaseProductImportTest {

    protected function importProducts(string $csvContent, string $behavior): void {
        parent::importProducts($csvContent, $behavior);
        self::reindexMview();
    }
}
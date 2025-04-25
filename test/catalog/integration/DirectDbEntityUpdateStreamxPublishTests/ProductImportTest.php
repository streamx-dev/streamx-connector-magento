<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseProductImportTest;

/**
 * @inheritdoc
 * @UpdateByScheduleIndexerMode
 */
class ProductImportTest extends BaseProductImportTest {

    protected function importProduct(string $csvContent, string $behavior): void {
        parent::importProduct($csvContent, $behavior);
        self::reindexMview();
    }
}
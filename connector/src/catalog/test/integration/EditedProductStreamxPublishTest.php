<?php

namespace StreamX\ConnectorCatalog\test\integration;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * See base class for prerequisites to run this test
 */
class EditedProductStreamxPublishTest extends BaseEditedEntityStreamxPublishTest {

    protected function getIndexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductEditedDirectlyInDatabaseToStreamx() {
        // given
        $productOldName = 'Joust Duffle Bag';
        $productNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $productId = MagentoMySqlQueryExecutor::getProductId($productOldName);

        // when
        self::renameProductInDb($productId, $productNewName);
        $this->indexerOperations->reindex();

        // then
        try {
            $expectedKey = "product_$productId";
            $this->assertDataIsPublished($expectedKey, $productNewName);
        } finally {
            self::renameProductInDb($productId, $productOldName);
        }
    }

    private static function renameProductInDb(int $productId, string $newName) {
        $productNameAttributeId = MagentoMySqlQueryExecutor::getProductNameAttributeId();
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE catalog_product_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        EOD);
    }
}
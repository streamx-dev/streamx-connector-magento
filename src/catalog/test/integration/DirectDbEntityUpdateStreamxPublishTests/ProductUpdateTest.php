<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class ProductUpdateTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductEditedDirectlyInDatabaseToStreamx() {
        // given
        $productOldName = 'Joust Duffle Bag';
        $productNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $productId = MagentoMySqlQueryExecutor::getProductId($productOldName);

        // and
        $expectedKey = "product_$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameProductInDb($productId, $productNewName);
        $this->reindexMview();

        // then
        try {
            $this->assertDataIsPublished($expectedKey, $productNewName);
        } finally {
            self::renameProductInDb($productId, $productOldName);
        }
    }

    private static function renameProductInDb(int $productId, string $newName) {
        $productNameAttributeId = MagentoMySqlQueryExecutor::getProductNameAttributeId();
        MagentoMySqlQueryExecutor::execute("
            UPDATE catalog_product_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        ");
    }
}
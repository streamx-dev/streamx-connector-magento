<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
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
        $productId = $this->db->getProductId($productOldName);

        // and
        $expectedKey = "product_$productId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->renameProductInDb($productId, $productNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsPublished($expectedKey, $productNewName);
        } finally {
            $this->renameProductInDb($productId, $productOldName);
        }
    }

    private function renameProductInDb(int $productId, string $newName) {
        $productNameAttributeId = $this->db->getProductNameAttributeId();
        $this->db->execute("
            UPDATE catalog_product_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        ");
    }
}
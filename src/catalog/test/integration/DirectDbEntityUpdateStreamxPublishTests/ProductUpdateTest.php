<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

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
        $productNewName = 'Name modified for testing';
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
            $this->assertExactDataIsPublished($expectedKey, 'edited-bag-product.json');
        } finally {
            $this->renameProductInDb($productId, $productOldName);
        }
    }

    private function renameProductInDb(int $productId, string $newName): void {
        $productNameAttributeId = $this->db->getProductNameAttributeId();
        $this->db->execute("
            UPDATE catalog_product_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        ");
    }
}
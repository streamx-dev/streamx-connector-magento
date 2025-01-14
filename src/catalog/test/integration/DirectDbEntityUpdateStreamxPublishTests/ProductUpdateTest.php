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
    public function shouldPublishSimpleProductEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Joust Duffle Bag', 'bag');
    }

    /** @test */
    public function shouldPublishConfigurableProductEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Chaz Kangeroo Hoodie', 'hoodie');
    }

    private function shouldPublishProductEditedDirectlyInDatabaseToStreamx(string $productName, string $productType): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = $this->db->getProductId($productName);

        // and
        $expectedKey = "product_$productId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->renameProductInDb($productId, $productNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, "edited-$productType-product.json");
        } finally {
            $this->renameProductInDb($productId, $productName);
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
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

    /** @test */
    public function shouldPublishBundleProductEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Sprite Yoga Companion Kit', 'bundle');
    }

    private function shouldPublishProductEditedDirectlyInDatabaseToStreamx(string $productName, string $productNameInValidationFileName): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = $this->db->getProductId($productName);

        // and
        $expectedKey = "pim:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->renameProductInDb($productId, $productNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, "edited-$productNameInValidationFileName-product.json");
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
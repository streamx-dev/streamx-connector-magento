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
        $this->setConfigurationValue($this->ADD_SWATCHES_PATH, '1');
        try {
            // TODO: currently swatches info is not added to the published product json even when the above setting is turned on
            $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Chaz Kangeroo Hoodie', 'hoodie');
        } finally {
            $this->restoreConfigurationValue($this->ADD_SWATCHES_PATH);
        }
    }

    private function shouldPublishProductEditedDirectlyInDatabaseToStreamx(string $productName, string $productType): void {
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
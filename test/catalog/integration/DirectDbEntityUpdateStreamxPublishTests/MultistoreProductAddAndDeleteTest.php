<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

/**
 * @inheritdoc
 * Additional prerequisites to run this test: a second Store must be created and configured:
 *  - Login as Admin to Magento
 *  - Create second store: Stores -> Settings -> All Stores
 *    - Create Store with any name and code; use "Default Category" as Root Category
 *    - Create Store View for the newly created store (using any name and code); select Status as "Enabled"
 *  - Go to StreamX Connector settings, add the new store view to the list of Stores to reindex
 *  - Open Streamx Ingestion settings, and set "pim_store_2:" as the product key prefix for the scope of the newly created store (view)
 */
class MultistoreProductAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    private const DEFAULT_STORE_ID = 0; // comes with markshust/docker-magento
    private const STORE_1_ID = 1; // comes with markshust/docker-magento

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProduct() {
        // given
        $productName = 'The minimal product';

        // when: insert product as enabled for all stores by default, but disabled for store 1:
        $productId = $this->insertProduct($productName, self::STORE_1_ID);

        // and
        $expectedKeyForStore1 = "pim:$productId";
        $expectedKeyForStore2 = "pim_store_2:$productId";
        $this->removeFromStreamX($expectedKeyForStore1, $expectedKeyForStore2);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKeyForStore2, 'added-minimal-product.json', [
                // mask variable parts (ids and generated sku)
                '"id": [0-9]+' => '"id": 0',
                '"sku": "[^"]+"' => '"sku": "[MASKED]"',
                '"the-minimal-product-[0-9]+"' => '"the-minimal-product-0"'
            ]);

            // and
            $this->assertDataIsNotPublished($expectedKeyForStore1);
        } finally {
            // and when
            $this->deleteProduct($productId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKeyForStore2);
        }
    }

    private function insertProduct(string $productName, int $storeIdWhereProductIsDisabled): int {
        $sku = (string) (new DateTime())->getTimestamp();

        $defaultStoreId = self::DEFAULT_STORE_ID;
        $websiteId = 1;
        $attributeSetId = $this->db->getDefaultProductAttributeSetId();
        $nameAttrId = $this->db->getProductAttributeId('name');
        $statusAttrId = $this->db->getProductAttributeId('status');
        $statusEnabled = Status::STATUS_ENABLED;
        $statusDisabled = Status::STATUS_DISABLED;

        $productId = $this->db->insert("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        $this->db->executeAll(["
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($productId, $nameAttrId, $defaultStoreId, '$productName')
        ", "
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, $statusAttrId, $defaultStoreId, $statusEnabled)
        ", "
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, $statusAttrId, $storeIdWhereProductIsDisabled, $statusDisabled) -- override product status for specific store
        ", "
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        "]);

        return $productId;
    }

    private function deleteProduct(int $productId): void {
        $this->db->executeAll([
            "DELETE FROM catalog_product_website WHERE product_id = $productId",
            "DELETE FROM catalog_product_entity_int WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity_varchar WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity WHERE entity_id = $productId"
        ]);
    }
}
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
    private const STORE_2_ID = 2; // manually created according to instructions in the class comments

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProduct() {
        // given: insert product as enabled for all stores by default, but disabled for store 1:
        $sku = (string) (new DateTime())->getTimestamp();
        $productId = $this->insertProduct(
            $sku,
            [
                self::DEFAULT_STORE_ID => 'Product name',
                self::STORE_1_ID => 'Product name in first store',
                self::STORE_2_ID => 'Product name in second store'
            ],
            [
                self::DEFAULT_STORE_ID => Status::STATUS_ENABLED,
                self::STORE_1_ID => Status::STATUS_DISABLED,
                self::STORE_2_ID => Status::STATUS_ENABLED
            ]
        );

        // and
        $expectedKeyForStore1 = "pim:$productId";
        $expectedKeyForStore2 = "pim_store_2:$productId";
        $this->removeFromStreamX($expectedKeyForStore1, $expectedKeyForStore2);

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKeyForStore2, 'added-multistore-product.json', [
                // provide values for placeholders in the validation file
                'SKU' => $sku,
                123456789 => $productId,
                'PRODUCT_NAME' => 'Product name in second store',
                'PRODUCT_SLUG' => "product-name-in-second-store-$productId"
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

    private function insertProduct(string $sku, array $storeIdProductNameMap, array $storeIdProductStatusMap): int {
        $websiteId = 1;
        $attributeSetId = $this->db->getDefaultProductAttributeSetId();
        $nameAttrId = $this->db->getProductAttributeId('name');
        $statusAttrId = $this->db->getProductAttributeId('status');

        // 1. Create product
        $productId = $this->db->insert("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        // 2. Add the product to the website's catalog
        $this->db->execute("
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        ");

        // 3. Set default and store-scoped names for the product
        foreach ($storeIdProductNameMap as $storeId => $productName) {
            $this->db->execute("
               INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                    ($productId, $nameAttrId, $storeId, '$productName')
            ");
        }

        // 4. Set default and store-scoped statuses for the product
        foreach ($storeIdProductStatusMap as $storeId => $productStatus) {
            $this->db->execute("
                INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                    ($productId, $statusAttrId, $storeId, $productStatus)
            ");
        }

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
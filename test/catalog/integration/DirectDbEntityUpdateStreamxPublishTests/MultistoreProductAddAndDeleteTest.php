<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductsFromWebsite() {
        // given: as in StoresControllerImpl, product 1 exists only in default website, product 4 exists in both websites

        // when: perform any change of both products - to trigger collecting their IDs by the mV iew feature
        self::$db->execute('UPDATE catalog_product_entity SET has_options = TRUE WHERE entity_id IN (1, 4)');

        $expectedPublishedKeys = [
            'pim:1',
            'pim:4',
            'pim_store_2:1',
            'pim_store_2:4',
            'pim_website_2:4'
        ];
        $unexpectedPublishedKey = 'pim_website_2:1';

        // and
        foreach ($expectedPublishedKeys as $key) {
            $this->removeFromStreamX($key);
        }
        $this->removeFromStreamX($unexpectedPublishedKey);

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished('pim:1', 'original-bag-product.json');
            $this->assertExactDataIsPublished('pim:4', 'wayfarer-bag-product.json');
            $this->assertExactDataIsPublished('pim_store_2:1', 'original-bag-product.json');
            $this->assertExactDataIsPublished('pim_store_2:4', 'wayfarer-bag-product.json');
            $this->assertExactDataIsPublished('pim_website_2:4', 'wayfarer-bag-product.json');

            // and
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        } finally {
            // restore DB changes
            self::$db->execute('UPDATE catalog_product_entity SET has_options = FALSE WHERE entity_id IN (1, 4)');
        }
    }

    /** @test */
    public function shouldPublishEnabledProduct() {
        // given: insert product as enabled for all stores by default, but disabled for store 1:
        $sku = (string) (new DateTime())->getTimestamp();
        $productId = $this->insertProduct(
            $sku,
            [
                self::DEFAULT_STORE_ID => 'Product name',
                self::STORE_1_ID => 'Product name in first store',
                parent::$store2Id => 'Product name in second store'
            ],
            [
                self::DEFAULT_STORE_ID => Status::STATUS_ENABLED,
                self::STORE_1_ID => Status::STATUS_DISABLED,
                parent::$store2Id => Status::STATUS_ENABLED
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
            $this->assertExactDataIsPublished($expectedKeyForStore2, 'added-minimal-product.json', [
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
        $websiteId = self::DEFAULT_WEBSITE_ID;
        $attributeSetId = self::$db->getDefaultProductAttributeSetId();
        $nameAttrId = self::$db->getProductAttributeId('name');
        $statusAttrId = self::$db->getProductAttributeId('status');

        // 1. Create product
        $productId = self::$db->insert("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        // 2. Add the product to the website's catalog
        self::$db->execute("
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        ");

        // 3. Set default and store-scoped names for the product
        foreach ($storeIdProductNameMap as $storeId => $productName) {
            self::$db->execute("
               INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                    ($productId, $nameAttrId, $storeId, '$productName')
            ");
        }

        // 4. Set default and store-scoped statuses for the product
        foreach ($storeIdProductStatusMap as $storeId => $productStatus) {
            self::$db->execute("
                INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                    ($productId, $statusAttrId, $storeId, $productStatus)
            ");
        }

        return $productId;
    }

    private function deleteProduct(int $productId): void {
        self::$db->executeAll([
            "DELETE FROM catalog_product_website WHERE product_id = $productId",
            "DELETE FROM catalog_product_entity_int WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity_varchar WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity WHERE entity_id = $productId"
        ]);
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductAddAndDeleteTest extends BaseMultistoreTest {

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
                parent::getStore2Id() => 'Product name in second store'
            ],
            [
                self::DEFAULT_STORE_ID => Status::STATUS_ENABLED,
                self::STORE_1_ID => Status::STATUS_DISABLED,
                parent::getStore2Id() => Status::STATUS_ENABLED
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
        $nameAttrId = self::$db->getProductAttributeId('name');
        $statusAttrId = self::$db->getProductAttributeId('status');

        // 1. Create product
        $productIds = self::$db->insertSimpleProduct($sku);
        $productId = $productIds[0];
        $entityId = $productIds[1];

        // 2. Add the product to the website's catalog
        self::$db->addProductToWebsite($entityId, $websiteId);

        // 3. Set default and store-scoped names for the product
        foreach ($storeIdProductNameMap as $storeId => $productName) {
            self::$db->insertVarcharProductAttribute($productId, $nameAttrId, $storeId, $productName);
        }

        // 4. Set default and store-scoped statuses for the product
        foreach ($storeIdProductStatusMap as $storeId => $productStatus) {
            self::$db->insertIntProductAttribute($productId, $statusAttrId, $storeId, $productStatus);
        }

        return $productId;
    }

    private function deleteProduct(int $productId): void {
        self::$db->deleteAll($productId, [
            'catalog_product_website' => 'product_id',
            'catalog_product_entity_int' => self::$db->getEntityAttributeLinkField(),
            'catalog_product_entity_varchar' => self::$db->getEntityAttributeLinkField(),
            'catalog_product_entity' => 'entity_id'
        ]);
    }
}
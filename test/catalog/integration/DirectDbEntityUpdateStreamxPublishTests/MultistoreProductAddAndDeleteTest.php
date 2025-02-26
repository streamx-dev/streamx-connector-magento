<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use Magento\Catalog\Model\Product\Visibility;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductsFromWebsite() {
        // given (as in StoresControllerImpl), the following products exist in both websites:
        //  - simple products 4, 5 and 6
        //  - product 61 that is a variant of configurable product 62
        $testedProductIds = [1, 4, 60, 61, 62];
        $testedStoreIds = [self::STORE_1_ID, self::$store2Id, self::$secondWebsiteStoreId];

        // when: perform any change of products - to trigger collecting their IDs by the mView feature. A good sample change is to make sure all are visible in the stores
        foreach ($testedStoreIds as $storeId) {
            self::$db->setProductsVisibleInStore($storeId, ...$testedProductIds);
        }

        $expectedPublishedKeys = [
            'pim:1',
            'pim:4',
            'pim:60',
            'pim:61',
            'pim:62', // note: editing parent product is expected to trigger publishing also all its variants

            'pim_store_2:1',
            'pim_store_2:4',
            'pim_store_2:60',
            'pim_store_2:61',
            'pim_store_2:62',

            'pim_website_2:4',
            'pim_website_2:61',
            'pim_website_2:62',
        ];

        $unexpectedPublishedKeys = [
            'pim_website_2:1', // those products are not available in the second website
            'pim_website_2:59',
            'pim_website_2:60'
        ];

        // and
        $this->removeFromStreamX(...$expectedPublishedKeys, ...$unexpectedPublishedKeys);

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished('pim:1', 'original-bag-product.json');
            $this->assertExactDataIsPublished('pim:4', 'wayfarer-bag-product.json');
            $this->assertExactDataIsPublished('pim:60', 'original-hoodie-xl-gray-product.json');
            $this->assertExactDataIsPublished('pim:61', 'original-hoodie-xl-orange-product.json');
            $this->assertExactDataIsPublished('pim:62', 'original-hoodie-product.json');

            $this->assertExactDataIsPublished('pim_store_2:1', 'original-bag-product.json');
            $this->assertExactDataIsPublished('pim_store_2:4', 'wayfarer-bag-product.json');
            $this->assertExactDataIsPublished('pim_store_2:60', 'original-hoodie-xl-gray-product.json');
            $this->assertExactDataIsPublished('pim_store_2:61', 'original-hoodie-xl-orange-product.json');
            $this->assertExactDataIsPublished('pim_store_2:62', 'original-hoodie-product.json');

            $this->assertExactDataIsPublished('pim_website_2:4', 'wayfarer-bag-product.json');
            $this->assertExactDataIsPublished('pim_website_2:61', 'original-hoodie-xl-orange-product.json');
            $this->assertExactDataIsPublished('pim_website_2:62', 'original-hoodie-product-in-second-website.json');

            // and
            foreach ($unexpectedPublishedKeys as $unexpectedPublishedKey) {
                $this->assertDataIsNotPublished($unexpectedPublishedKey);
            }
        } finally {
            // restore DB changes
            foreach ($testedStoreIds as $storeId) {
                self::$db->unsetProductsVisibleInStore($storeId, ...$testedProductIds);
            }
        }
    }

    /** @test */
    public function shouldPublishEnabledAndVisibleProduct() {
        // given: insert two products, with different status/visibility settings
        $sku1 = (string) (new DateTime())->getTimestamp();
        $product1 = $this->insertProduct(
            $sku1,
            [
                self::DEFAULT_STORE_ID => 'Default name of Product A',
                self::STORE_1_ID => 'Name of Product A in first store',
                parent::$store2Id => 'Name of Product A in second store'
            ],
            [
                self::DEFAULT_STORE_ID => Status::STATUS_ENABLED,
                self::STORE_1_ID => Status::STATUS_DISABLED,
                parent::$store2Id => Status::STATUS_ENABLED
            ],
            [
                self::DEFAULT_STORE_ID => Visibility::VISIBILITY_NOT_VISIBLE, // enabled but not visible product - should not be exported
                self::STORE_1_ID => Visibility::VISIBILITY_IN_CATALOG, // disabled but visible product - should not be exported
                parent::$store2Id => Visibility::VISIBILITY_IN_SEARCH // enabled and visible product - should be exported
            ]
        );
        $product1Id = $product1->getEntityId();

        $sku2 = $sku1.'2';
        $product2 = $this->insertProduct(
            $sku2,
            [
                self::DEFAULT_STORE_ID => 'Default name of Product B',
                self::STORE_1_ID => 'Name of Product B in first store',
                parent::$store2Id => 'Name of Product B in second store'
            ],
            [
                self::DEFAULT_STORE_ID => Status::STATUS_ENABLED,
                self::STORE_1_ID => Status::STATUS_ENABLED,
                parent::$store2Id => Status::STATUS_DISABLED
            ],
            [
                self::DEFAULT_STORE_ID => Visibility::VISIBILITY_BOTH, // enabled and visible product - should be exported
                self::STORE_1_ID => Visibility::VISIBILITY_IN_CATALOG, // enabled and visible product - should be exported
                parent::$store2Id => Visibility::VISIBILITY_NOT_VISIBLE // disabled and not visible product - should not be exported
            ]
        );
        $product2Id = $product2->getEntityId();

        // and
        $expectedKeyForProduct1 = "pim_store_2:$product1Id";
        $expectedKeyForProduct2 = "pim:$product2Id";

        $unexpectedKeyForProduct1 = "pim:$product1Id";
        $unexpectedKeyForProduct2 = "pim_store_2:$product2Id";

        $this->removeFromStreamX($unexpectedKeyForProduct2, $expectedKeyForProduct2, $unexpectedKeyForProduct1, $expectedKeyForProduct1);

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKeyForProduct1, 'added-minimal-product.json', [
                // provide values for placeholders in the validation file
                'SKU' => $sku1,
                123456789 => $product1Id,
                'PRODUCT_NAME' => 'Name of Product A in second store',
                'PRODUCT_SLUG' => "name-of-product-a-in-second-store-$product1Id",
                'VISIBILITY' => 'Search'
            ]);

            $this->assertExactDataIsPublished($expectedKeyForProduct2, 'added-minimal-product.json', [
                // provide values for placeholders in the validation file
                'SKU' => $sku2,
                123456789 => $product2Id,
                'PRODUCT_NAME' => 'Name of Product B in first store',
                'PRODUCT_SLUG' => "name-of-product-b-in-first-store-$product2Id",
                'VISIBILITY' => 'Catalog'
            ]);

            // and
            $this->assertDataIsNotPublished($unexpectedKeyForProduct1);
            $this->assertDataIsNotPublished($unexpectedKeyForProduct2);
        } finally {
            // and when
            $this->deleteProduct($product1);
            $this->deleteProduct($product2);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKeyForProduct1);
            $this->assertDataIsUnpublished($expectedKeyForProduct2);
        }
    }

    private function insertProduct(string $sku, array $storeIdProductNameMap, array $storeIdProductStatusMap, array $storeIdProductVisibilityMap): EntityIds {
        $websiteId = self::DEFAULT_WEBSITE_ID;
        $nameAttrId = self::$db->getProductAttributeId('name');
        $statusAttrId = self::$db->getProductAttributeId('status');
        $visibilityAttrId = self::$db->getProductAttributeId('visibility');

        // 1. Create product
        $product = self::$db->insertProduct($sku, $websiteId);
        $linkFieldId = $product->getLinkFieldId();

        // 2. Set default and store-scoped names for the product
        foreach ($storeIdProductNameMap as $storeId => $productName) {
            self::$db->insertVarcharProductAttribute($linkFieldId, $nameAttrId, $storeId, $productName);
        }

        // 3. Set default and store-scoped statuses for the product
        foreach ($storeIdProductStatusMap as $storeId => $productStatus) {
            self::$db->insertIntProductAttribute($linkFieldId, $statusAttrId, $storeId, $productStatus);
        }

        // 4. Set default and store-scoped visibilities for the product
        foreach ($storeIdProductVisibilityMap as $storeId => $productVisibility) {
            self::$db->insertIntProductAttribute($linkFieldId, $visibilityAttrId, $storeId, $productVisibility);
        }

        return $product;
    }

    private function deleteProduct(EntityIds $productIds): void {
        self::$db->deleteById($productIds->getLinkFieldId(), [
            'catalog_product_entity_int' => self::$db->getEntityAttributeLinkField(),
            'catalog_product_entity_varchar' => self::$db->getEntityAttributeLinkField()
        ]);

        self::$db->deleteById($productIds->getEntityId(), [
            'catalog_product_website' => 'product_id',
            'catalog_product_entity' => 'entity_id'
        ]);

        if (self::$db->isEnterpriseMagento()) {
            self::$db->deleteById($productIds->getEntityId(), [
                'sequence_product' => 'sequence_value'
            ]);
        }
    }
}
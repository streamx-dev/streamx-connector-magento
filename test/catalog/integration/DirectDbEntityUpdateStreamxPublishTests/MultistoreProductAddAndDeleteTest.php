<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

/**
 * @inheritdoc
 */
class MultistoreProductAddAndDeleteTest extends BaseMultistoreTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductsFromWebsite() {
        // given (as in StoresControllerImpl), the following products exist in both websites:
        //  - simple products 4, 5 and 6
        //  - product 61 that is a variant of configurable product 62

        // when: perform any change of products - to trigger collecting their IDs by the mView feature
        $productsUpdateQuery  = 'UPDATE catalog_product_entity SET attribute_set_id = attribute_set_id + 1 WHERE entity_id IN (1, 4, 62)';
        $productsRestoreQuery = 'UPDATE catalog_product_entity SET attribute_set_id = attribute_set_id - 1 WHERE entity_id IN (1, 4, 62)';
        $this->db->execute($productsUpdateQuery);

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
            $this->db->execute($productsRestoreQuery);
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
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
        // given: insert product as enabled for all stores by default, but disabled for store 1:
        $productIdOnlyInDefaultWebsite = 1;
        $productIdInBothWebsites = 4; // as in how-to-setup-local-development-environment.md

        // when change name of products at non-default store levels:
        $this->addStoreLevelProductName(self::DEFAULT_WEBSITE_ID, self::STORE_2_ID, $productIdOnlyInDefaultWebsite);
        $this->addStoreLevelProductName(self::DEFAULT_WEBSITE_ID, self::STORE_2_ID, $productIdInBothWebsites);

        // and
        $expectedPublishedKeys = [
            "pim:$productIdInBothWebsites",
            "pim:$productIdOnlyInDefaultWebsite",
            "pim_store_2:$productIdOnlyInDefaultWebsite",
            "pim_store_2:$productIdInBothWebsites",
            "pim_store_3:$productIdInBothWebsites"
        ];
        $unexpectedPublishedKeys = [
            "pim_store_3:$productIdOnlyInDefaultWebsite"
        ];

        foreach ($expectedPublishedKeys as $key) {
            $this->removeFromStreamX($key);
        }
        foreach ($unexpectedPublishedKeys as $key) {
            $this->removeFromStreamX($key);
        }

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertPublished('pim:1', 'Joust Duffle Bag');
            $this->assertPublished('pim:4', 'Wayfarer Messenger Bag');
            $this->assertPublished('pim_store_2:1', 'Name of product 1 in store 2 of website 1');
            $this->assertPublished('pim_store_2:4', 'Name of product 4 in store 2 of website 1');
            $this->assertPublished('pim_store_3:4', 'Wayfarer Messenger Bag');

            // and
            foreach ($unexpectedPublishedKeys as $key) {
                $this->assertDataIsNotPublished($key);
            }
        } finally {
            $this->db->deleteLastRow('catalog_category_entity_varchar', 'value_id');
            $this->db->deleteLastRow('catalog_category_entity_varchar', 'value_id');
        }
    }

    private function addStoreLevelProductName(int $websiteId, int $storeId, int $productId): void {
        $productNameAttributeId = $this->db->getProductNameAttributeId();
        $this->db->execute("
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES (
                $productId,
                $productNameAttributeId,
                $storeId,
                'Name of product $productId in store $storeId of website $websiteId'
            )
        ");
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
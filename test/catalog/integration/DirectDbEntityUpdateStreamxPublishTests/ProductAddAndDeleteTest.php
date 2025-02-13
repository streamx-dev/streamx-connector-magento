<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

/**
 * @inheritdoc
 */
class ProductAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    private const PRODUCT_PRICE = 350;
    private const INDEXED_PRICE = 370;
    private const DISCOUNTED_PRICE = 330;

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishMinimalProductAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProduct() {
        // given
        $productName = 'The minimal product';
        $sku = (string) (new DateTime())->getTimestamp();

        // when
        $productId = $this->insertNewMinimalProduct($sku, $productName);
        $expectedKey = "pim:$productId";

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'added-minimal-product.json', [
                // provide values for placeholders in the validation file
                'SKU' => $sku,
                123456789 => $productId,
                'PRODUCT_NAME' => 'The minimal product',
                'PRODUCT_SLUG' => "the-minimal-product-$productId"
            ]);
        } finally {
            // and when
            $this->deleteProduct($productId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /** @test */
    public function shouldPublishProductAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProduct() {
        // given
        $productName = 'The new great watch';
        $categoryIds = [
            $this->db->getCategoryId('Watches'),
            $this->db->getCategoryId('Collections'), // note: this category is not active in sample data by default
            $this->db->getCategoryId('Sale')
        ];

        // when
        $this->allowIndexingAllAttributes();
        $productId = $this->insertNewProduct($productName, $categoryIds);
        $expectedKey = "pim:$productId";

        try {
            // and
            $this->reindexMview();

            // then
            $publishedJson = $this->assertExactDataIsPublished($expectedKey, 'added-watch-product.json', [
                // mask variable parts (ids and generated sku)
                '"id": [0-9]+' => '"id": 0',
                '"sku": "[^"]+"' => '"sku": "[MASKED]"',
                '"the-new-great-watch-[0-9]+"' => '"the-new-great-watch-0"',
                '"option_id": "[0-9]+"' => '"option_id": "0"',
                '"option_type_id": "[0-9]+"' => '"option_type_id": "0"',
                // expect the indexed prices to be applied
                '"value": ' . self::PRODUCT_PRICE => '"value": ' . self::INDEXED_PRICE,
                '"discountedValue": ' . self::PRODUCT_PRICE => '"discountedValue": ' . self::DISCOUNTED_PRICE
            ]);

            // and
            $this->assertStringContainsString('Watches', $publishedJson);
            $this->assertStringNotContainsString('Collections', $publishedJson);
            $this->assertStringContainsString('Sale', $publishedJson);
        } finally {
            try {
                // and when
                $this->deleteProduct($productId);
                $this->reindexMview();

                // then
                $this->assertDataIsUnpublished($expectedKey);
            } finally {
                $this->restoreDefaultIndexingAttributes();
            }
        }
    }

    /** @test */
    public function shouldNotPublishNotActiveProduct() {
        // given
        $productName = 'The second watch';
        $watchesCategoryId = $this->db->getCategoryId('Watches');

        // when
        $productId = $this->insertNewProduct($productName, [$watchesCategoryId]);
        $expectedKey = "pim:$productId";

        // and: make the product not active:
        $defaultStoreId = 0;
        $this->db->execute("
            UPDATE catalog_product_entity_int
               SET value = " . Status::STATUS_DISABLED . "
             WHERE entity_id = $productId
               AND attribute_id = " . self::attrId('status') . "
               AND store_id = $defaultStoreId
               AND value = " . Status::STATUS_ENABLED . "
        ");

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsNotPublished($expectedKey);
        } finally {
            $this->deleteProduct($productId);
        }
    }

    /** @test */
    public function shouldPublishMultipleProductsAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProducts() {
        // given
        $productsCount = 200;

        // when
        $productNames = [];
        $skus = [];
        $productIds = [];
        for ($i = 0; $i < $productsCount; $i++) {
            $productNames[] = "Watch $i";
            $skus[] = (string) (new DateTime())->getTimestamp();
            $productIds[] = $this->insertNewMinimalProduct($skus[$i], $productNames[$i]);
        }

        try {
            // and
            $this->reindexMview();

            // then
            for ($i = 0; $i < $productsCount; $i++) {
                $productId = $productIds[$i];
                $productName = $productNames[$i];
                $expectedSlug = str_replace([' ', 'W'], ['-', 'w'], $productName) . "-$productId";
                $this->assertExactDataIsPublished("pim:$productId", 'added-minimal-product.json', [
                    // provide values for placeholders in the validation file
                    'SKU' => $skus[$i],
                    123456789 => $productId,
                    'PRODUCT_NAME' => $productName,
                    'PRODUCT_SLUG' => $expectedSlug
                ]);
            }
        } finally {
            // and when
            for ($i = 0; $i < $productsCount; $i++) {
                $this->deleteProduct($productIds[$i]);
            }
            $this->reindexMview();

            // then
            for ($i = 0; $i < $productsCount; $i++) {
                $this->assertDataIsUnpublished('pim:' . $productIds[$i]);
            }
        }
    }

    /**
     * Inserts new minimal product to database
     * @return int ID of the inserted product
     */
    private function insertNewMinimalProduct(string $sku, string $productName): int {
        $defaultStoreId = 0;
        $websiteId = 1;
        $attributeSetId = $this->db->getDefaultProductAttributeSetId();

        $productId = $this->db->insert("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        $this->db->executeAll(["
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('name') . ", $defaultStoreId, '$productName')
        ", "
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('status') . ", $defaultStoreId, " . Status::STATUS_ENABLED . ")
        ", "
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        "]);

        return $productId;
    }

    /**
     * Inserts new product to database
     * @return int ID of the inserted product
     */
    private function insertNewProduct(string $productName, array $categoryIds): int {
        $sku = (string) (new DateTime())->getTimestamp();
        $productInternalName = strtolower(str_replace(' ', '-', $productName));

        $defaultStoreId = 0;
        $stockId = 1;
        $quantity = 100;
        $websiteId = 1;
        $brownColorId = $this->db->getAttributeOptionId('color', 'Brown');
        $metalMaterialId = $this->db->getAttributeOptionId('material', 'Metal');
        $plasticMaterialId = $this->db->getAttributeOptionId('material', 'Plastic');
        $leatherMaterialId = $this->db->getAttributeOptionId('material', 'Leather');

        $attributeSetId = $this->db->getDefaultProductAttributeSetId();

        $productId = $this->db->insert("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('name') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('meta_title') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('meta_description') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('url_key') . ", $defaultStoreId, '$productInternalName')
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_text (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('material') . ", $defaultStoreId, '$metalMaterialId,$plasticMaterialId,$leatherMaterialId')
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_decimal (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('price') . ", $defaultStoreId, " . self::PRODUCT_PRICE . ")
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('visibility') . ", $defaultStoreId, 4), -- visibility in Catalog and Search
                ($productId, " . self::attrId('status') . ", $defaultStoreId, " . Status::STATUS_ENABLED . "),
                ($productId, " . self::attrId('color') . ", $defaultStoreId, $brownColorId)
        ");

        $this->db->execute("
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        ");

        foreach ($categoryIds as $categoryId) {
            $this->db->execute("
                INSERT INTO catalog_category_product (category_id, product_id, position) VALUES
                    ($categoryId, $productId, 0)
            ");
        }

        $this->db->execute("
            INSERT INTO cataloginventory_stock_item (product_id, stock_id, qty, is_in_stock, is_qty_decimal, manage_stock) VALUES
                ($productId, $stockId, $quantity, TRUE, FALSE, TRUE)
        ");

        $this->db->execute("
            INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty, stock_status) VALUES
                ($productId, $websiteId, $stockId, $quantity, 1)
        ");

        $this->db->execute("
            INSERT INTO catalog_product_index_price (entity_id, customer_group_id, website_id, price, final_price) VALUES
                ($productId, 0, $websiteId, " . self::INDEXED_PRICE . ", " . self::DISCOUNTED_PRICE . ")
        ");

        $this->addProductOption($productId, $defaultStoreId);

        return $productId;
    }

    private function addProductOption(int $productId, int $storeId): void {
        $optionId = $this->db->insert("INSERT INTO catalog_product_option (product_id, type, is_require, sort_order) VALUES ($productId, 'drop_down', 1, 0)");
        $optionTypeId = $this->db->insert("INSERT INTO catalog_product_option_type_value (option_id, sort_order) VALUES($optionId, 0)");

        $this->db->execute("INSERT INTO catalog_product_option_title (option_id, store_id, title) VALUES ($optionId, $storeId, 'Size')");
        $this->db->execute("INSERT INTO catalog_product_option_type_title (option_type_id, store_id, title) VALUES ($optionTypeId, $storeId, 'The size')");

        $this->db->execute("INSERT INTO catalog_product_option_price (option_id, store_id, price, price_type) VALUES ($optionId, $storeId, 1.23, 'fixed')");
        $this->db->execute("INSERT INTO catalog_product_option_type_price (option_type_id, store_id, price, price_type) VALUES ($optionTypeId, $storeId, 9.87, 'fixed')");
    }

    private function deleteProduct(int $productId): void {
        $this->db->executeAll([
            "DELETE FROM catalog_product_index_price WHERE entity_id = $productId",
            "DELETE FROM cataloginventory_stock_status WHERE product_id = $productId",
            "DELETE FROM cataloginventory_stock_item WHERE product_id = $productId",
            "DELETE FROM catalog_category_product WHERE product_id = $productId",
            "DELETE FROM catalog_product_website WHERE product_id = $productId",
            "DELETE FROM catalog_product_entity_int WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity_decimal WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity_varchar WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity WHERE entity_id = $productId"
        ]);
        $this->deleteProductOption();
    }

    private function deleteProductOption(): void {
        $this->db->deleteLastRow('catalog_product_option_type_price', 'option_type_price_id');
        $this->db->deleteLastRow('catalog_product_option_price', 'option_price_id');
        $this->db->deleteLastRow('catalog_product_option_type_title', 'option_type_title_id');
        $this->db->deleteLastRow('catalog_product_option_title', 'option_title_id');
        $this->db->deleteLastRow('catalog_product_option_type_value', 'option_type_id');
        $this->db->deleteLastRow('catalog_product_option', 'option_id');
    }

    private function attrId($attrCode): string {
        return $this->db->getProductAttributeId($attrCode);
    }
}
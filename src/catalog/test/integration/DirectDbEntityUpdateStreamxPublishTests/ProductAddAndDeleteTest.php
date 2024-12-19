<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor as DB;

/**
 * @inheritdoc
 */
class ProductAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProduct() {
        // given
        $watchesCategoryId = DB::getCategoryId('Watches');
        $productName = 'The new great watch!';

        // when
        $productId = self::insertNewProduct($productName, $watchesCategoryId);
        $this->reindexMview();

        // then
        $expectedKey = "product_$productId";
        try {
            $this->assertDataIsPublished($expectedKey, $productName);
        } finally {
            // and when
            self::deleteProduct($productId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /** @test */
    public function shouldPublishMultipleProductsAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProducts() {
        // given
        $watchesCategoryId = DB::getCategoryId('Watches');
        $product1Name = 'First new watch';
        $product2Name = 'Second new watch';
        $product3Name = 'Third new watch';

        // when
        $product1Id = self::insertNewProduct($product1Name, $watchesCategoryId);
        $product2Id = self::insertNewProduct($product2Name, $watchesCategoryId);
        $product3Id = self::insertNewProduct($product3Name, $watchesCategoryId);
        $this->reindexMview();

        // then
        $expectedProduct1Key = 'product_' . $product1Id;
        $expectedProduct2Key = 'product_' . $product2Id;
        $expectedProduct3Key = 'product_' . $product3Id;

        try {
            $this->assertDataIsPublished($expectedProduct1Key, $product1Name);
            $this->assertDataIsPublished($expectedProduct2Key, $product2Name);
            $this->assertDataIsPublished($expectedProduct3Key, $product3Name);
        } finally {
            // and when
            self::deleteProduct($product1Id);
            self::deleteProduct($product2Id);
            self::deleteProduct($product3Id);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedProduct1Key);
            $this->assertDataIsUnpublished($expectedProduct2Key);
            $this->assertDataIsUnpublished($expectedProduct3Key);
        }
    }

    /**
     * Inserts new product to database
     * @return int ID of the inserted product
     */
    private static function insertNewProduct(string $productName, string $categoryId): int {
        $sku = (string) (new DateTime())->getTimestamp();
        $productInternalName = strtolower(str_replace(' ', '_', $productName));

        $defaultStoreId = 0;
        $stockId = 1;
        $quantity = 100;
        $price = 35;
        $websiteId = 1;

        $attributeSetId = DB::getDefaultCategoryAttributeSetId();

        DB::execute("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        $productId = DB::selectFirstField("
            SELECT MAX(entity_id)
              FROM catalog_product_entity
        ");

        DB::execute("
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('name') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('meta_title') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('meta_description') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('url_key') . ", $defaultStoreId, '$productInternalName')
        ");

        DB::execute("
            INSERT INTO catalog_product_entity_decimal (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('price') . ", $defaultStoreId, $price)
        ");

        DB::execute("
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('visibility') . ", $defaultStoreId, 4), -- visibility in Catalog and Search
                ($productId, " . self::attrId('status') . ", $defaultStoreId, 1), -- enabled
                ($productId, " . self::attrId('tax_class_id') . ", $defaultStoreId, 2) -- standard tax rate                
        ");

        DB::execute("
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        ");

        DB::execute("
            INSERT INTO catalog_category_product (category_id, product_id, position) VALUES
                ($categoryId, $productId, 0)
        ");

        DB::execute("
            INSERT INTO cataloginventory_stock_item (product_id, stock_id, qty, is_in_stock, is_qty_decimal, manage_stock) VALUES
                ($productId, $stockId, $quantity, TRUE, FALSE, TRUE)
        ");

        DB::execute("
            INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty, stock_status) VALUES
                ($productId, $websiteId, $stockId, $quantity, 1)
        ");

        return $productId;
    }

    private static function deleteProduct(int $productId): void {
        DB::executeAll([
            "DELETE FROM cataloginventory_stock_status WHERE product_id = $productId",
            "DELETE FROM cataloginventory_stock_item WHERE product_id = $productId",
            "DELETE FROM catalog_category_product WHERE product_id = $productId",
            "DELETE FROM catalog_product_website WHERE product_id = $productId",
            "DELETE FROM catalog_product_entity_int WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity_decimal WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity_varchar WHERE entity_id = $productId",
            "DELETE FROM catalog_product_entity WHERE entity_id = $productId"
        ]);
    }

    private static function attrId($attrCode): string {
        return DB::getProductAttributeId($attrCode);
    }
}
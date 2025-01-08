<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

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
        $watchesCategoryId = $this->db->getCategoryId('Watches');
        $productName = 'The new great watch!';

        // when
        $productId = $this->insertNewProduct($productName, $watchesCategoryId);
        $expectedKey = "product_$productId";

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsPublished($expectedKey, $productName);
        } finally {
            // and when
            $this->deleteProduct($productId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /** @test */
    public function shouldPublishMultipleProductsAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProducts() {
        // given
        $watchesCategoryId = $this->db->getCategoryId('Watches');
        $productsCount = 200;

        // when
        $productNames = [];
        $productIds = [];
        for ($i = 0; $i < $productsCount; $i++) {
            $productNames[] = "New watch $i";
            $productIds[] = $this->insertNewProduct($productNames[$i], $watchesCategoryId);
        }

        try {
            // and
            $this->reindexMview();

            // then
            for ($i = 0; $i < $productsCount; $i++) {
                $this->assertDataIsPublished('product_' . $productIds[$i], $productNames[$i]);
            }
        } finally {
            // and when
            for ($i = 0; $i < $productsCount; $i++) {
                $this->deleteProduct($productIds[$i]);
            }
            $this->reindexMview();

            // then
            for ($i = 0; $i < $productsCount; $i++) {
                $this->assertDataIsUnpublished('product_' . $productIds[$i]);
            }
        }
    }

    /**
     * Inserts new product to database
     * @return int ID of the inserted product
     */
    private function insertNewProduct(string $productName, string $categoryId): int {
        $sku = (string) (new DateTime())->getTimestamp();
        $productInternalName = strtolower(str_replace(' ', '_', $productName));

        $defaultStoreId = 0;
        $stockId = 1;
        $quantity = 100;
        $price = 35;
        $websiteId = 1;

        $attributeSetId = $this->db->getDefaultCategoryAttributeSetId();

        $this->db->execute("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        $productId = $this->db->selectFirstField("
            SELECT MAX(entity_id)
              FROM catalog_product_entity
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('name') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('meta_title') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('meta_description') . ", $defaultStoreId, '$productName'),
                ($productId, " . self::attrId('url_key') . ", $defaultStoreId, '$productInternalName')
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_decimal (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('price') . ", $defaultStoreId, $price)
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('visibility') . ", $defaultStoreId, 4), -- visibility in Catalog and Search
                ($productId, " . self::attrId('status') . ", $defaultStoreId, 1), -- enabled
                ($productId, " . self::attrId('tax_class_id') . ", $defaultStoreId, 2) -- standard tax rate                
        ");

        $this->db->execute("
            INSERT INTO catalog_product_website (product_id, website_id) VALUES
                ($productId, $websiteId)
        ");

        $this->db->execute("
            INSERT INTO catalog_category_product (category_id, product_id, position) VALUES
                ($categoryId, $productId, 0)
        ");

        $this->db->execute("
            INSERT INTO cataloginventory_stock_item (product_id, stock_id, qty, is_in_stock, is_qty_decimal, manage_stock) VALUES
                ($productId, $stockId, $quantity, TRUE, FALSE, TRUE)
        ");

        $this->db->execute("
            INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty, stock_status) VALUES
                ($productId, $websiteId, $stockId, $quantity, 1)
        ");

        return $productId;
    }

    private function deleteProduct(int $productId): void {
        $this->db->executeAll([
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

    private function attrId($attrCode): string {
        return $this->db->getProductAttributeId($attrCode);
    }
}
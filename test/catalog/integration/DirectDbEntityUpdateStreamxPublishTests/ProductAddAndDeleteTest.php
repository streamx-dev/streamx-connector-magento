<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Msrp\Model\Product\Attribute\Source\Type\Price;
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
        $productName = 'The new great watch';

        // when
        $this->allowIndexingAllAttributes();
        $productId = $this->insertNewProduct($productName, $watchesCategoryId);
        $expectedKey = "pim:$productId";

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'added-watch-product.json', [
                '"id": [0-9]+' => '"id": 0',
                '"sku": "[^"]+"' => '"sku": "[MASKED]"',
                '"the-new-great-watch-[0-9]+"' => '"the-new-great-watch-0"',
                '"option_id": "[0-9]+"' => '"option_id": "0"',
                '"option_type_id": "[0-9]+"' => '"option_type_id": "0"',
            ]);
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
                $this->assertDataIsPublished('pim:' . $productIds[$i], $productNames[$i]);
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
     * Inserts new product to database
     * @return int ID of the inserted product
     */
    private function insertNewProduct(string $productName, string $categoryId): int {
        $sku = (string) (new DateTime())->getTimestamp();
        $productInternalName = strtolower(str_replace(' ', '-', $productName));

        $defaultStoreId = 0;
        $stockId = 1;
        $quantity = 100;
        $price = 35;
        $websiteId = 1;
        $brownColorId = $this->db->getAttributeOptionId('color', 'Brown');
        $metalMaterialId = $this->db->getAttributeOptionId('material', 'Metal');
        $plasticMaterialId = $this->db->getAttributeOptionId('material', 'Plastic');
        $leatherMaterialId = $this->db->getAttributeOptionId('material', 'Leather');

        $attributeSetId = $this->db->getDefaultCategoryAttributeSetId();

        $this->db->execute("
            INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) VALUES
                ($attributeSetId, 'simple', '$sku', FALSE, FALSE)
        ");

        $productId = $this->db->selectMaxId('catalog_product_entity', 'entity_id');

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
                ($productId, " . self::attrId('price') . ", $defaultStoreId, $price)
        ");

        $this->db->execute("
            INSERT INTO catalog_product_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($productId, " . self::attrId('visibility') . ", $defaultStoreId, 4), -- visibility in Catalog and Search
                ($productId, " . self::attrId('status') . ", $defaultStoreId, 1), -- enabled
                ($productId, " . self::attrId('color') . ", $defaultStoreId, $brownColorId)
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

        $this->addProductOption($productId, $defaultStoreId);

        return $productId;
    }

    private function addProductOption(int $productId, int $defaultStoreId): void {
        $this->db->execute("INSERT INTO catalog_product_option (product_id, type, is_require, sort_order) VALUES ($productId, 'drop_down', 1, 0)");
        $optionId = $this->db->selectMaxId('catalog_product_option', 'option_id');

        $this->db->execute("INSERT INTO catalog_product_option_type_value (option_id, sort_order) VALUES($optionId, 0)");
        $optionTypeId = $this->db->selectMaxId('catalog_product_option_type_value', 'option_type_id');

        $this->db->execute("INSERT INTO catalog_product_option_title (option_id, store_id, title) VALUES ($optionId, $defaultStoreId, 'Size')");
        $this->db->execute("INSERT INTO catalog_product_option_type_title (option_type_id, store_id, title) VALUES ($optionTypeId, $defaultStoreId, 'The size')");

        $this->db->execute("INSERT INTO catalog_product_option_price (option_id, store_id, price, price_type) VALUES ($optionId, $defaultStoreId, 1.23, 'fixed')");
        $this->db->execute("INSERT INTO catalog_product_option_type_price (option_type_id, store_id, price, price_type) VALUES ($optionTypeId, $defaultStoreId, 9.87, 'fixed')");
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
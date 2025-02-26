<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use DateTime;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    private const PRODUCT_PRICE = 350;
    private const INDEXED_PRICE = 370;
    private const DISCOUNTED_PRICE = 330;

    /** @test */
    public function shouldPublishMinimalProductAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProduct() {
        // given
        $productName = 'The minimal product';
        $sku = (string) (new DateTime())->getTimestamp();

        // when
        $product = $this->insertNewMinimalProduct($sku, $productName);
        $productId = $product->getEntityId();
        $expectedKey = "default_product:$productId";

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'added-minimal-product.json', [
                // provide values for placeholders in the validation file
                'SKU' => $sku,
                123456789 => $productId,
                'PRODUCT_NAME' => 'The minimal product',
                'PRODUCT_SLUG' => "the-minimal-product-$productId",
                'VISIBILITY' => 'Catalog, Search'
            ]);
        } finally {
            // and when
            $this->deleteProduct($product);
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
            self::$db->getCategoryId('Watches'),
            self::$db->getCategoryId('Collections'), // note: this category is not active in sample data by default
            self::$db->getCategoryId('Sale')
        ];

        // when
        $this->allowIndexingAllProductAttributes();
        $product = $this->insertNewProduct($productName, $categoryIds);
        $productId = $product->getEntityId();
        $expectedKey = "default_product:$productId";

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
                $this->deleteProduct($product);
                $this->reindexMview();

                // then
                $this->assertDataIsUnpublished($expectedKey);
            } finally {
                $this->restoreDefaultIndexedProductAttributes();
            }
        }
    }

    /** @test */
    public function shouldNotPublishNotActiveProduct() {
        // given
        $productName = 'The second watch';
        $watchesCategoryId = self::$db->getCategoryId('Watches');

        // when
        $product = $this->insertNewProduct($productName, [$watchesCategoryId]);
        $productId = $product->getEntityId();
        $linkFieldId = $product->getLinkFieldId();

        $expectedKey = "default_product:$productId";

        // and: make the product not active:
        $defaultStoreId = self::DEFAULT_STORE_ID;
        $linkField = self::$db->getEntityAttributeLinkField();
        self::$db->execute("
            UPDATE catalog_product_entity_int
               SET value = " . Status::STATUS_DISABLED . "
             WHERE $linkField = $linkFieldId
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
            $this->deleteProduct($product);
        }
    }

    /** @test */
    public function shouldPublishMultipleProductsAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedProducts() {
        // given
        $productsCount = 200;

        // when
        $productNames = [];
        $skus = [];
        $productsIds = [];
        for ($i = 0; $i < $productsCount; $i++) {
            $productNames[] = "Watch $i";
            $skus[] = (string) (new DateTime())->getTimestamp();
            $productsIds[] = $this->insertNewMinimalProduct($skus[$i], $productNames[$i]);
        }

        try {
            // and
            $this->reindexMview();

            // then
            for ($i = 0; $i < $productsCount; $i++) {
                $productId = $productsIds[$i]->getEntityId();
                $productName = $productNames[$i];
                $expectedSlug = str_replace([' ', 'W'], ['-', 'w'], $productName) . "-$productId";
                $this->assertExactDataIsPublished("default_product:$productId", 'added-minimal-product.json', [
                    // provide values for placeholders in the validation file
                    'SKU' => $skus[$i],
                    123456789 => $productId,
                    'PRODUCT_NAME' => $productName,
                    'PRODUCT_SLUG' => $expectedSlug,
                    'VISIBILITY' => 'Catalog, Search'
                ]);
            }
        } finally {
            // and when
            for ($i = 0; $i < $productsCount; $i++) {
                $this->deleteProduct($productsIds[$i]);
            }
            $this->reindexMview();

            // then
            for ($i = 0; $i < $productsCount; $i++) {
                $this->assertDataIsUnpublished('default_product:' . $productsIds[$i]->getEntityId());
            }
        }
    }

    private function insertNewMinimalProduct(string $sku, string $productName): EntityIds {
        $defaultStoreId = self::DEFAULT_STORE_ID;
        $websiteId = self::DEFAULT_WEBSITE_ID;

        $product = self::$db->insertProduct($sku, $websiteId);
        $linkFieldId = $product->getLinkFieldId();

        self::$db->insertVarcharProductAttribute($linkFieldId, self::attrId('name'), $defaultStoreId, $productName);
        self::$db->insertIntProductAttribute($linkFieldId, self::attrId('status'), $defaultStoreId, Status::STATUS_ENABLED);
        self::$db->insertIntProductAttribute($linkFieldId, self::attrId('visibility'), $defaultStoreId, Visibility::VISIBILITY_BOTH);

        return $product;
    }

    private function insertNewProduct(string $productName, array $categoryIds): EntityIds {
        $sku = (string) (new DateTime())->getTimestamp();
        $productInternalName = strtolower(str_replace(' ', '-', $productName));

        $defaultStoreId = self::DEFAULT_STORE_ID;
        $stockId = 1;
        $quantity = 100;
        $websiteId = self::DEFAULT_WEBSITE_ID;
        $brownColorId = self::$db->getAttributeOptionId('color', 'Brown');
        $metalMaterialId = self::$db->getAttributeOptionId('material', 'Metal');
        $plasticMaterialId = self::$db->getAttributeOptionId('material', 'Plastic');
        $leatherMaterialId = self::$db->getAttributeOptionId('material', 'Leather');

        $product = self::$db->insertProduct($sku, $websiteId);
        $productId = $product->getEntityId();
        $linkFieldId = $product->getLinkFieldId();

        self::$db->insertVarcharProductAttribute($linkFieldId, self::attrId('name'), $defaultStoreId, $productName);
        self::$db->insertVarcharProductAttribute($linkFieldId, self::attrId('meta_title'), $defaultStoreId, $productName);
        self::$db->insertVarcharProductAttribute($linkFieldId, self::attrId('meta_description'), $defaultStoreId, $productName);
        self::$db->insertVarcharProductAttribute($linkFieldId, self::attrId('url_key'), $defaultStoreId, $productInternalName);
        self::$db->insertTextProductAttribute($linkFieldId, self::attrId('material'), $defaultStoreId, "$metalMaterialId,$plasticMaterialId,$leatherMaterialId");
        self::$db->insertDecimalProductAttribute($linkFieldId, self::attrId('price'), $defaultStoreId, self::PRODUCT_PRICE);
        self::$db->insertIntProductAttribute($linkFieldId, self::attrId('visibility'), $defaultStoreId, Visibility::VISIBILITY_BOTH);
        self::$db->insertIntProductAttribute($linkFieldId, self::attrId('status'), $defaultStoreId, Status::STATUS_ENABLED);
        self::$db->insertIntProductAttribute($linkFieldId, self::attrId('color'), $defaultStoreId, $brownColorId);

        foreach ($categoryIds as $categoryId) {
            self::$db->execute("
                INSERT INTO catalog_category_product (category_id, product_id, position) VALUES
                    ($categoryId, $productId, 0)
            ");
        }

        self::$db->execute("
            INSERT INTO cataloginventory_stock_item (product_id, stock_id, qty, is_in_stock, is_qty_decimal, manage_stock) VALUES
                ($productId, $stockId, $quantity, TRUE, FALSE, TRUE)
        ");

        self::$db->execute("
            INSERT INTO cataloginventory_stock_status (product_id, website_id, stock_id, qty, stock_status) VALUES
                ($productId, $websiteId, $stockId, $quantity, 1)
        ");

        self::$db->execute("
            INSERT INTO catalog_product_index_price (entity_id, customer_group_id, website_id, price, final_price) VALUES
                ($productId, 0, $websiteId, " . self::INDEXED_PRICE . ", " . self::DISCOUNTED_PRICE . ")
        ");

        $this->addProductOption($linkFieldId, $defaultStoreId);

        return $product;
    }

    private function addProductOption(int $productId, int $storeId): void {
        $optionId = self::$db->insert("INSERT INTO catalog_product_option (product_id, type, is_require, sort_order) VALUES ($productId, 'drop_down', 1, 0)");
        $optionTypeId = self::$db->insert("INSERT INTO catalog_product_option_type_value (option_id, sort_order) VALUES($optionId, 0)");

        self::$db->execute("INSERT INTO catalog_product_option_title (option_id, store_id, title) VALUES ($optionId, $storeId, 'Size')");
        self::$db->execute("INSERT INTO catalog_product_option_type_title (option_type_id, store_id, title) VALUES ($optionTypeId, $storeId, 'The size')");

        self::$db->execute("INSERT INTO catalog_product_option_price (option_id, store_id, price, price_type) VALUES ($optionId, $storeId, 1.23, 'fixed')");
        self::$db->execute("INSERT INTO catalog_product_option_type_price (option_type_id, store_id, price, price_type) VALUES ($optionTypeId, $storeId, 9.87, 'fixed')");
    }

    private function deleteProduct(EntityIds $productIds): void {
        self::$db->deleteById($productIds->getEntityId(), [
            'catalog_product_index_price' => 'entity_id',
            'cataloginventory_stock_status' => 'product_id',
            'cataloginventory_stock_item' => 'product_id',
            'catalog_category_product' => 'product_id',
            'catalog_product_website' => 'product_id',
            'catalog_product_entity' => 'entity_id'
        ]);

        self::$db->deleteById($productIds->getLinkFieldId(), [
            'catalog_product_entity_decimal' => self::$db->getEntityAttributeLinkField(),
            'catalog_product_entity_int' => self::$db->getEntityAttributeLinkField(),
            'catalog_product_entity_varchar' => self::$db->getEntityAttributeLinkField(),
            'catalog_product_entity_text' => self::$db->getEntityAttributeLinkField()
        ]);

        if (self::$db->isEnterpriseMagento()) {
            self::$db->deleteById($productIds->getEntityId(), [
                'sequence_product' => 'sequence_value'
            ]);
        }

        $this->deleteProductOption();
    }

    private function deleteProductOption(): void {
        self::$db->deleteLastRow('catalog_product_option_type_price', 'option_type_price_id');
        self::$db->deleteLastRow('catalog_product_option_price', 'option_price_id');
        self::$db->deleteLastRow('catalog_product_option_type_title', 'option_type_title_id');
        self::$db->deleteLastRow('catalog_product_option_title', 'option_title_id');
        self::$db->deleteLastRow('catalog_product_option_type_value', 'option_type_id');
        self::$db->deleteLastRow('catalog_product_option', 'option_id');
    }

    private static function attrId($attrCode): string {
        return self::$db->getProductAttributeId($attrCode);
    }
}
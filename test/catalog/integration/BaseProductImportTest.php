<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Magento\ImportExport\Model\Import;
use StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests\BaseDirectDbEntityUpdateTest;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\FileUtils;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 * @UsesProductIndexer
 */
abstract class BaseProductImportTest extends BaseStreamxConnectorPublishTest {

    private const PRODUCT_PRICE = '10.01';
    private const EDITED_PRODUCT_PRICE = '10.02';

    private const FURNITURE_CATEGORY_JSON_FILE = 'imported/furniture-category.json';
    private const WOODEN_CATEGORY_JSON_FILE = 'imported/wooden-category.json';
    private const TABLES_CATEGORY_JSON_FILE = 'imported/tables-category.json';

    private EntityIds $furnitureCategoryId;
    private EntityIds $woodenCategoryId;
    private EntityIds $tablesCategoryId;

    /** @test */
    public function shouldPublishProductAndCategoriesFromImportFile_AndUnpublishDeletedProduct() {
        // given
        $csvContent = self::readTestResourceFile('product_import.csv');
        $validationFile = 'imported/B072ZLCB3M-product.json';

        // when 1: admin imports the file to Magento
        $this->importProducts($csvContent, Import::BEHAVIOR_ADD_UPDATE);

        // then
        // a) First, assert the category tree is created
        $this->furnitureCategoryId = self::$db->getCategoryId('Furniture');
        $this->woodenCategoryId = self::$db->getCategoryId('Wooden');
        $this->tablesCategoryId = self::$db->getCategoryId('Tables');
        self::assertCategoryIsPublished(self::categoryKey($this->furnitureCategoryId), self::FURNITURE_CATEGORY_JSON_FILE);
        self::assertCategoryIsPublished(self::categoryKey($this->woodenCategoryId), self::WOODEN_CATEGORY_JSON_FILE);
        self::assertCategoryIsPublished(self::categoryKey($this->tablesCategoryId), self::TABLES_CATEGORY_JSON_FILE);

        // b) Then, assert the product is created
        $expectedId = self::getMaxProductId();
        $expectedKey = self::productKeyFromEntityId($expectedId);
        self::assertProductIsPublished($expectedKey, $validationFile, self::PRODUCT_PRICE);

        // when 2: admin wants to update the product in Magento - edits the csv file and reuploads it
        $csvContent = str_replace(self::PRODUCT_PRICE, self::EDITED_PRODUCT_PRICE, $csvContent);
        $this->importProducts($csvContent, Import::BEHAVIOR_ADD_UPDATE);

        // then
        self::assertProductIsPublished($expectedKey, $validationFile, self::EDITED_PRODUCT_PRICE);

        // when 3: admin wants to replace the product completely
        $this->importProducts($csvContent, Import::BEHAVIOR_REPLACE);

        // then: expecting the product to be unpublished from original key, and published with new key (as a new product with ID +1)
        self::assertDataIsUnpublished($expectedKey);
        $newExpectedId = $expectedId + 1;
        $newExpectedKey = self::productKeyFromEntityId($newExpectedId);
        self::assertProductIsPublished($newExpectedKey, $validationFile, self::EDITED_PRODUCT_PRICE);

        // when 4: admin wants to delete the product via the Import feature
        $this->importProducts($csvContent, Import::BEHAVIOR_DELETE);

        // then
        self::assertDataIsUnpublished($newExpectedKey);

        // cleanup: deleting a product via Admin's Product Import feature never deletes any categories along with deleted products - so do it manually
        self::deleteCategory($this->furnitureCategoryId); // should delete also the two subcategories
    }

    /** @test */
    public function shouldPublishMultipleProductsAndCategoriesFromImportFile_AndUnpublishDeletedProducts() {
        // given
        $csvContent = self::readTestResourceFile('minimal_products_import.csv');

        // when: admin imports the file to Magento
        $this->importProducts($csvContent, Import::BEHAVIOR_ADD_UPDATE);
        $this->furnitureCategoryId = self::$db->getCategoryId('Furniture');
        $this->woodenCategoryId = self::$db->getCategoryId('Wooden');
        $this->tablesCategoryId = self::$db->getCategoryId('Tables');

        // then: assert all products are created
        $expectedId1 = self::getMaxProductId() - 2;
        $expectedId2 = $expectedId1 + 1;
        $expectedId3 = $expectedId1 + 2;
        $expectedKey1 = self::productKeyFromEntityId($expectedId1);
        $expectedKey2 = self::productKeyFromEntityId($expectedId2);
        $expectedKey3 = self::productKeyFromEntityId($expectedId3);
        self::assertProductIsPublished($expectedKey1, 'imported/X072ZLCB3M-product.json');
        self::assertProductIsPublished($expectedKey2, 'imported/Y072ZLCB3M-product.json');
        self::assertProductIsPublished($expectedKey3, 'imported/Z072ZLCB3M-product.json');

        // when 2: admin wants to delete the products via the Import feature
        $this->importProducts($csvContent, Import::BEHAVIOR_DELETE);

        // then
        self::assertDataIsUnpublished($expectedKey1);
        self::assertDataIsUnpublished($expectedKey2);
        self::assertDataIsUnpublished($expectedKey3);

        // cleanup: deleting a product via Admin's Product Import feature never deletes any categories along with deleted products - so do it manually
        self::deleteCategory($this->furnitureCategoryId); // should delete also the two subcategories
    }

    protected function importProducts(string $csvContent, string $behavior): void {
        MagentoEndpointsCaller::call('products/import', [
            'csvContent' => $csvContent,
            'behavior' => $behavior
        ]);
    }

    private function assertCategoryIsPublished(string $key, string $jsonFile): void {
        self::assertExactDataIsPublished($key, $jsonFile, [
            $this->furnitureCategoryId->getEntityId() => '10000',
            $this->woodenCategoryId->getEntityId() => '10001',
            $this->tablesCategoryId->getEntityId() => '10002'
        ]);
    }

    private function assertProductIsPublished(string $key, string $jsonFile, string $expectedPrice = self::PRODUCT_PRICE): void {
        $expectedId = explode(':', $key)[1];
        self::assertExactDataIsPublished($key, $jsonFile, [
            $expectedId => '12345',
            "/[a-z0-9_]+\\.jpg" => '/RANDOM.jpg',
            $expectedPrice => self::PRODUCT_PRICE,
            $this->furnitureCategoryId->getEntityId() => '10000',
            $this->woodenCategoryId->getEntityId() => '10001',
            $this->tablesCategoryId->getEntityId() => '10002'
        ]);
    }

    private function deleteCategory(EntityIds $categoryId): void {
        MagentoEndpointsCaller::call('category/delete', [
            'categoryId' => $categoryId->getEntityId()
        ]);
    }

    private static function readTestResourceFile(string $name): string {
        $folder = FileUtils::findFolder('test/resources');
        $csvFilePath = "$folder/$name";
        return file_get_contents($csvFilePath);
    }

    private static function getMaxProductId(): int {
        return self::$db->selectSingleValue('SELECT MAX(entity_id) FROM catalog_product_entity');
    }
}
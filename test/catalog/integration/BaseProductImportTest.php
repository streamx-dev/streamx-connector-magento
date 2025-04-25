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

    private const productPrice = '10.01';
    private const editedProductPrice = '10.02';

    private const PRODUCT_JSON_FILE = 'imported/B072ZLCB3M-product.json';
    private const FURNITURE_CATEGORY_JSON_FILE = 'imported/furniture-category.json';
    private const WOODEN_CATEGORY_JSON_FILE = 'imported/wooden-category.json';
    private const TABLES_CATEGORY_JSON_FILE = 'imported/tables-category.json';

    private EntityIds $furnitureCategoryId;
    private EntityIds $woodenCategoryId;
    private EntityIds $tablesCategoryId;

    /** @test */
    public function shouldPublishProductAndCategoriesFromImportFile_AndUnpublishDeletedProduct() {
        // given
        $csvFilePath = FileUtils::findFolder('test/resources') . '/product_import.csv';
        $csvContent = file_get_contents($csvFilePath);

        // when 1: admin imports the file to Magento
        $this->importProduct($csvContent, Import::BEHAVIOR_ADD_UPDATE);

        // then
        // a) First, assert the category tree is created
        $this->furnitureCategoryId = self::$db->getCategoryId('Furniture');
        $this->woodenCategoryId = self::$db->getCategoryId('Wooden');
        $this->tablesCategoryId = self::$db->getCategoryId('Tables');
        self::assertCategoryIsPublished(self::categoryKey($this->furnitureCategoryId), self::FURNITURE_CATEGORY_JSON_FILE);
        self::assertCategoryIsPublished(self::categoryKey($this->woodenCategoryId), self::WOODEN_CATEGORY_JSON_FILE);
        self::assertCategoryIsPublished(self::categoryKey($this->tablesCategoryId), self::TABLES_CATEGORY_JSON_FILE);

        // b) Then, assert the product is created
        $expectedId = self::$db->selectSingleValue('SELECT MAX(entity_id) FROM catalog_product_entity');
        $expectedKey = self::productKeyFromEntityId($expectedId);
        self::assertProductIsPublished($expectedKey, $expectedId, self::productPrice);

        // when 2: admin wants to update the product in Magento - edits the csv file and reuploads it
        $csvContent = str_replace(self::productPrice, self::editedProductPrice, $csvContent);
        $this->importProduct($csvContent, Import::BEHAVIOR_ADD_UPDATE);

        // then
        self::assertProductIsPublished($expectedKey, $expectedId, self::editedProductPrice);

        // when 3: admin wants to replace the product completely
        $this->importProduct($csvContent, Import::BEHAVIOR_REPLACE);

        // then: expecting the product to be unpublished from original key, and published with new key (as a new product with ID +1)
        self::assertDataIsUnpublished($expectedKey);
        $newExpectedId = $expectedId + 1;
        $newExpectedKey = self::productKeyFromEntityId($newExpectedId);
        self::assertProductIsPublished($newExpectedKey, $newExpectedId, self::editedProductPrice);

        // when 4: admin wants to delete the product via the Import feature
        $this->importProduct($csvContent, Import::BEHAVIOR_DELETE);

        // then
        self::assertDataIsUnpublished($newExpectedKey);

        // cleanup: deleting a product via Admin's Product Import feature never deletes any categories along with deleted products - so do it manually
        self::deleteCategory($this->furnitureCategoryId); // should delete also the two subcategories
    }

    protected function importProduct(string $csvContent, string $behavior): void {
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

    private function assertProductIsPublished(string $key, int $expectedId, string $expectedPrice): void {
        self::assertExactDataIsPublished($key, self::PRODUCT_JSON_FILE, [
            $expectedId => '12345',
            "/[a-z0-9_]+\\.jpg" => '/RANDOM.jpg',
            $expectedPrice => self::productPrice,
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
}
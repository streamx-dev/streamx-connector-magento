<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Magento\ImportExport\Model\Import;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\FileUtils;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 */
abstract class BaseProductImportTest extends BaseStreamxConnectorPublishTest {

    const INDEXER_IDS = [CategoryProcessor::INDEXER_ID, ProductProcessor::INDEXER_ID];

    private const PRODUCT_PRICE = '10';
    private const EDITED_PRODUCT_PRICE = '11';

    private const FURNITURE_CATEGORY_JSON_FILE = 'imported/furniture-category.json';
    private const WOODEN_CATEGORY_JSON_FILE = 'imported/wooden-category.json';
    private const TABLES_CATEGORY_JSON_FILE = 'imported/tables-category.json';

    private int $furnitureCategoryId;
    private int $woodenCategoryId;
    private int $tablesCategoryId;

    /** @test */
    public function shouldPublishProductAndCategoriesFromImportFile_AndUnpublishDeletedProduct() {
        // given
        $csvContent = self::readTestResourceFile('product_import.csv');
        $validationFile = 'imported/B072ZLCB3M-product.json';

        // when 1: admin imports the file to Magento
        $this->importProducts($csvContent, Import::BEHAVIOR_ADD_UPDATE);

        // then
        // a) First, assert the category tree is created
        $this->furnitureCategoryId = self::$db->getCategoryId('Furniture')->getEntityId();
        $this->woodenCategoryId = self::$db->getCategoryId('Wooden')->getEntityId();
        $this->tablesCategoryId = self::$db->getCategoryId('Tables')->getEntityId();
        self::assertCategoryIsPublished($this->furnitureCategoryId, self::FURNITURE_CATEGORY_JSON_FILE);
        self::assertCategoryIsPublished($this->woodenCategoryId, self::WOODEN_CATEGORY_JSON_FILE);
        self::assertCategoryIsPublished($this->tablesCategoryId, self::TABLES_CATEGORY_JSON_FILE);

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
        $this->furnitureCategoryId = self::$db->getCategoryId('Furniture')->getEntityId();
        $this->woodenCategoryId = self::$db->getCategoryId('Wooden')->getEntityId();
        $this->tablesCategoryId = self::$db->getCategoryId('Tables')->getEntityId();

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

    private function assertCategoryIsPublished(int $categoryId, string $jsonFile): void {
        $key = self::categoryKeyFromEntityId($categoryId);
        self::assertExactDataIsPublished($key, $jsonFile, $this->getCategoryIdReplacements());
    }

    private function assertProductIsPublished(string $key, string $jsonFile, string $expectedPrice = self::PRODUCT_PRICE): void {
        $actualId = explode(':', $key)[1];
        $regexReplacements = $this->getCategoryIdReplacements();
        self::addIdReplacement($regexReplacements, $actualId, 12345);
        $regexReplacements["/[a-z0-9_]+\\.jpg"] = '/RANDOM.jpg';
        $regexReplacements[$expectedPrice] = self::PRODUCT_PRICE;
        self::assertExactDataIsPublished($key, $jsonFile, $regexReplacements);
    }

    private function getCategoryIdReplacements(): array {
        $regexReplacements = [];
        self::addIdReplacement($regexReplacements, $this->furnitureCategoryId, 10000);
        self::addIdReplacement($regexReplacements, $this->woodenCategoryId, 10001);
        self::addIdReplacement($regexReplacements, $this->tablesCategoryId, 10002);
        return $regexReplacements;
    }

    private static function addIdReplacement(array &$regexReplacements, int $actualId, int $idInValidationFile): void {
        $regexReplacements['"' . $actualId . '"'] = '"' . $idInValidationFile . '"'; // whole "id" field
        $regexReplacements['-' . $actualId . '"'] = '-' . $idInValidationFile . '"'; // end of -id" field in slugs
    }

    private function deleteCategory(int $categoryId): void {
        MagentoEndpointsCaller::call('category/delete', [
            'categoryId' => $categoryId
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
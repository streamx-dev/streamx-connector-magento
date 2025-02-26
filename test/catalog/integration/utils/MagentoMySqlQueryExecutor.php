<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;
use Magento\Catalog\Model\Product\Visibility;
use mysqli;

class MagentoMySqlQueryExecutor {

    private const SERVER_NAME = "127.0.0.1";

    // below settings as in magento/env/db.env file
    private const USER = "magento";
    private const PASSWORD = "magento";
    private const DB_NAME = "magento";

    private mysqli $connection;

    /**
     * either row_id (enterprise/cloud version) or entity_id (community version)
     */
    private string $entityAttributeLinkField;
    private bool $isEnterpriseMagento;

    public function __construct() {
        $this->connection = new mysqli(self::SERVER_NAME, self::USER, self::PASSWORD, self::DB_NAME);

        $this->entityAttributeLinkField = $this->selectSingleValue("
            SELECT COLUMN_NAME
              FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = '" . self::DB_NAME . "'
               AND TABLE_NAME = 'catalog_product_entity_varchar'
               AND COLUMN_NAME IN ('row_id', 'entity_id')
        ");
        $this->isEnterpriseMagento = $this->entityAttributeLinkField === 'row_id';
    }

    public function disconnect(): void {
        $this->connection->close();
    }

    public function getEntityAttributeLinkField(): string {
        return $this->entityAttributeLinkField;
    }

    public function isEnterpriseMagento(): bool {
        return $this->isEnterpriseMagento;
    }

    /**
     * Returns the value of first field from first row found by the given query
     */
    public function selectSingleValue(string $selectQuery) {
        $result = $this->connection->query($selectQuery);
        $row = $result->fetch_row();
        $result->close();

        if (!$row) {
            throw new Exception("No rows found for $selectQuery");
        }
        if (count($row) !== 1) {
            throw new Exception("Expected a single field in the query: $selectQuery");
        }
        return $row[0];
    }

    public function selectRows(string $selectQuery): array {
        $result = $this->connection->query($selectQuery);
        $values = [];
        while ($row = $result->fetch_row()) {
            $values[] = array_values($row);
        }
        $result->close();
        return $values;
    }

    /**
     * @return int last inserted ID
     * @throws Exception
     */
    public function insert(string $insertQuery): int {
        $this->execute($insertQuery);
        return $this->selectSingleValue('SELECT LAST_INSERT_ID()');
    }

    public function execute(string $query): void {
        if (!$this->connection->query($query)) {
            throw new Exception("Query $query failed: " . $this->connection->error);
        }
    }

    public function deleteById(int $id, array $tableNameAndIdColumnList): void {
        foreach ($tableNameAndIdColumnList as $tableName => $idColumn) {
            $this->execute("DELETE FROM $tableName WHERE $idColumn = $id");
        }
    }

    public function getProductId(string $productName): string {
        $productNameAttributeId = $this->getProductNameAttributeId();

        return $this->selectSingleValue("
            SELECT $this->entityAttributeLinkField
              FROM catalog_product_entity_varchar
             WHERE attribute_id = $productNameAttributeId
               AND value = '$productName'
        ");
    }

    public function getProductIdsAndNamesMap(string $productNamePrefix): array {
        $productNameAttributeId = $this->getProductNameAttributeId();

        $rows = $this->selectRows("
            SELECT DISTINCT entity_id, value
              FROM catalog_product_entity_varchar
             WHERE attribute_id = $productNameAttributeId
               AND value LIKE '$productNamePrefix%'
        ");

        $result = [];
        foreach ($rows as $row) {
            $result[$row[0]] = $row[1];
        }
        return $result;
    }

    public function getCategoryId(string $categoryName): string {
        $categoryNameAttributeId = $this->getCategoryNameAttributeId();

        return $this->selectSingleValue("
            SELECT $this->entityAttributeLinkField
              FROM catalog_category_entity_varchar
             WHERE attribute_id = $categoryNameAttributeId
               AND value = '$categoryName'
        ");
    }

    public function getProductAttributeId(string $attributeCode): string {
        $productEntityTypeId = $this->getProductEntityTypeId();
        return $this->getAttributeId($attributeCode, $productEntityTypeId);
    }

    public function getCategoryAttributeId(string $attributeCode): string {
        $categoryEntityTypeId = $this->getCategoryEntityTypeId();
        return $this->getAttributeId($attributeCode, $categoryEntityTypeId);
    }

    public function getProductNameAttributeId(): string {
        $productEntityTypeId = $this->getProductEntityTypeId();
        return $this->getNameAttributeId($productEntityTypeId);
    }

    public function getCategoryNameAttributeId(): string {
        $categoryEntityTypeId = $this->getCategoryEntityTypeId();
        return $this->getNameAttributeId($categoryEntityTypeId);
    }

    public function getProductEntityTypeId(): string {
        return $this->getEntityTypeId('catalog_product_entity');
    }

    public function getCategoryEntityTypeId(): string {
        return $this->getEntityTypeId('catalog_category_entity');
    }

    private function getEntityTypeId(string $table): string {
        return $this->selectSingleValue("
            SELECT entity_type_id
              FROM eav_entity_type
             WHERE entity_table = '$table'
        ");
    }

    public function getNameAttributeId(int $entityTypeId): string {
        return $this->getAttributeId('name', $entityTypeId);
    }

    public function getAttributeId(string $attributeCode, int $entityTypeId): string {
        return $this->selectSingleValue("
            SELECT attribute_id
              FROM eav_attribute
             WHERE attribute_code = '$attributeCode'
               AND entity_type_id = $entityTypeId
        ");
    }

    public function getAttributeDisplayName(int $attributeId): string {
        return $this->selectSingleValue("
            SELECT frontend_label
              FROM eav_attribute
             WHERE attribute_id = $attributeId
        ");
    }

    private function getDefaultAttributeSetId(string $table): int {
        $entityTypeId = $this->getEntityTypeId($table);
        return $this->selectSingleValue("
            SELECT attribute_set_id
              FROM eav_attribute_set
             WHERE entity_type_id = $entityTypeId
              AND attribute_set_name = 'Default'
        ");
    }

    public function getAttributeOptionId(string $attributeCode, string $attributeValueLabel): int {
        return $this->selectSingleValue("
            SELECT v.option_id
              FROM eav_attribute_option_value v
              JOIN eav_attribute_option o ON o.option_id = v.option_id
              JOIN eav_attribute a ON a.attribute_id = o.attribute_id
             WHERE a.attribute_code = '$attributeCode'
               AND v.value = '$attributeValueLabel'
        ");
    }

    public function deleteLastRow(string $table, string $idColumn): void {
        $this->execute("
            DELETE FROM $table
             ORDER BY $idColumn DESC
             LIMIT 1
        ");
    }

    public function insertProduct(string $sku, int $websiteId): EntityIds {
        $attributeSetId = $this->getDefaultAttributeSetId('catalog_product_entity');

        if ($this->isEnterpriseMagento) {
            $entityId = $this->selectSingleValue('SELECT 1 + MAX(sequence_value) FROM sequence_product');
            $this->execute("INSERT INTO sequence_product(sequence_value) VALUES($entityId)");
            $rowId = $this->insert("
                INSERT INTO catalog_product_entity (entity_id, attribute_set_id, type_id, sku, has_options, required_options) 
                                            VALUES ($entityId, $attributeSetId, 'simple', '$sku', FALSE, FALSE)
            ");
            $entityIds = new EntityIds($entityId, $rowId);
        } else {
            $entityId = $this->insert("
                INSERT INTO catalog_product_entity (attribute_set_id, type_id, sku, has_options, required_options) 
                                            VALUES ($attributeSetId, 'simple', '$sku', FALSE, FALSE)     
            ");
            $entityIds = new EntityIds($entityId, $entityId);
        }

        // now product can be added to website
        $this->execute("
            INSERT INTO catalog_product_website (product_id, website_id) 
                                         VALUES ({$entityIds->getEntityId()}, $websiteId)
        ");
        return $entityIds;
    }

    public function insertCategory(int $parentCategoryId, string $rootPath): EntityIds {
        $attributeSetId = $this->getDefaultAttributeSetId('catalog_category_entity');
        $level = substr_count($rootPath, '/');

        if ($this->isEnterpriseMagento) {
            $entityId = $this->selectSingleValue('SELECT 1 + MAX(sequence_value) FROM sequence_catalog_category');
            $this->execute("INSERT INTO sequence_catalog_category(sequence_value) VALUES($entityId)");
            $rowId = $this->insert("
                INSERT INTO catalog_category_entity (entity_id, attribute_set_id, parent_id, path, position, level, children_count)
                                             VALUES ($entityId, $attributeSetId, $parentCategoryId, '', 1, $level, 0)
            ");
            $entityIds = new EntityIds($entityId, $rowId);
        } else {
            $entityId = $this->insert("
                INSERT INTO catalog_category_entity (attribute_set_id, parent_id, path, position, level, children_count)
                                             VALUES ($attributeSetId, $parentCategoryId, '', 1, $level, 0)
            ");
            $entityIds = new EntityIds($entityId, $entityId);
        }

        // now path can be set
        $this->execute("
            UPDATE catalog_category_entity
               SET path = '$rootPath/$entityId'
             WHERE entity_id = {$entityIds->getEntityId()}
        ");

        // insert basic attributes
        self::insertVarcharCategoryAttribute($entityIds->getLinkFieldId(), self::getCategoryAttributeId('display_mode'), 0, 'PRODUCTS');
        self::insertIntCategoryAttribute($entityIds->getLinkFieldId(), self::getCategoryAttributeId('include_in_menu'), 0, 1);

        return $entityIds;
    }

    public function renameProduct(int $productId, string $newName): void {
        $productNameAttributeId = $this->getProductNameAttributeId();
        $this->execute("
            UPDATE catalog_product_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $productNameAttributeId
               AND $this->entityAttributeLinkField = $productId
        ");
    }

    public function renameCategory(int $categoryId, string $newName): void {
        $categoryNameAttributeId = $this->getCategoryNameAttributeId();
        $this->execute("
            UPDATE catalog_category_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $categoryNameAttributeId
               AND $this->entityAttributeLinkField = $categoryId
        ");
    }

    public function insertIntProductAttribute(int $productId, int $attributeId, int $storeId, $attributeValue): void {
        $this->insertEntityAttribute('catalog_product_entity_int', $productId, $attributeId, $storeId, $attributeValue);
    }
    public function insertDecimalProductAttribute(int $productId, int $attributeId, int $storeId, $attributeValue): void {
        $this->insertEntityAttribute('catalog_product_entity_decimal', $productId, $attributeId, $storeId, $attributeValue);
    }
    public function insertVarcharProductAttribute(int $productId, int $attributeId, int $storeId, $attributeValue): void {
        $this->insertEntityAttribute('catalog_product_entity_varchar', $productId, $attributeId, $storeId, $attributeValue);
    }
    public function insertTextProductAttribute(int $productId, int $attributeId, int $storeId, $attributeValue): void {
        $this->insertEntityAttribute('catalog_product_entity_text', $productId, $attributeId, $storeId, $attributeValue);
    }

    public function insertIntCategoryAttribute(int $categoryId, int $attributeId, int $storeId, $attributeValue): void {
        $this->insertEntityAttribute('catalog_category_entity_int', $categoryId, $attributeId, $storeId, $attributeValue);
    }
    public function insertVarcharCategoryAttribute(int $categoryId, int $attributeId, int $storeId, $attributeValue): void {
        $this->insertEntityAttribute('catalog_category_entity_varchar', $categoryId, $attributeId, $storeId, $attributeValue);
    }

    private function insertEntityAttribute(string $tableName, int $entityId, int $attributeId, int $storeId, $attributeValue): void {
        $columns = "$this->entityAttributeLinkField, attribute_id, store_id, value";
        $this->execute("REPLACE INTO $tableName ($columns) VALUES ($entityId, $attributeId, $storeId, '$attributeValue')");
    }

    public function deleteIntProductAttribute(int $productId, int $attributeId, int $storeId): void {
        $this->deleteEntityAttribute('catalog_product_entity_int', $productId, $attributeId, $storeId);
    }

    private function deleteEntityAttribute(string $tableName, int $entityId, int $attributeId, int $storeId): void {
        $this->execute("
            DELETE FROM $tableName
             WHERE $this->entityAttributeLinkField = $entityId
               AND attribute_id = $attributeId
               AND store_id = $storeId
        ");
    }

    public function setProductsVisibleInStore(int $storeId, int... $productIds): void {
        $visibilityAttributeId = self::getProductAttributeId('visibility');
        foreach ($productIds as $productId) {
            self::insertIntProductAttribute($productId, $visibilityAttributeId, $storeId, Visibility::VISIBILITY_BOTH);
        }
    }

    public function unsetProductsVisibleInStore(int $storeId, int... $productIds): void {
        $visibilityAttributeId = self::getProductAttributeId('visibility');
        foreach ($productIds as $productId) {
            self::deleteIntProductAttribute($productId, $visibilityAttributeId, $storeId);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;
use mysqli;

class MagentoMySqlQueryExecutor {

    private const SERVER_NAME = "127.0.0.1";

    // below settings as in magento/env/db.env file
    private const USER = "magento";
    private const PASSWORD = "magento";
    private const DB_NAME = "magento";

    private ?mysqli $connection = null;

    public function connect(): void {
        $this->connection = new mysqli(self::SERVER_NAME, self::USER, self::PASSWORD, self::DB_NAME);
    }

    public function disconnect(): void {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Returns the value of first field from first row, or null or error or no result data
     */
    public function selectFirstField(string $selectQuery) {
        $result = $this->connection->query($selectQuery);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_row();
            $result->free();
            return $row[0];
        } else {
            return null;
        }
    }

    public function execute(string $query): void {
        $result = $this->connection->query($query);
        if (!$result) {
            throw new Exception("Query $query failed: " . $this->connection->error);
        }
    }

    public function executeAll(array $queries): void {
        foreach ($queries as $query) {
            $this->execute($query);
        }
    }

    public function getProductId(string $productName): string {
        $productNameAttributeId = $this->getProductNameAttributeId();

        return $this->selectFirstField("
            SELECT entity_id
              FROM catalog_product_entity_varchar
             WHERE attribute_id = $productNameAttributeId
               AND value = '$productName'
        ");
    }

    public function getCategoryId(string $categoryName): string {
        $categoryNameAttributeId = $this->getCategoryNameAttributeId();

        return $this->selectFirstField("
            SELECT entity_id
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
        return $this->selectFirstField("
            SELECT entity_type_id
              FROM eav_entity_type
             WHERE entity_table = '$table'
        ");
    }

    public function getNameAttributeId(int $entityTypeId): string {
        return $this->getAttributeId('name', $entityTypeId);
    }

    public function getAttributeId(string $attributeCode, int $entityTypeId): string {
        return $this->selectFirstField("
            SELECT attribute_id
              FROM eav_attribute
             WHERE attribute_code = '$attributeCode'
               AND entity_type_id = $entityTypeId
        ");
    }

    public function getAttributeDisplayName(int $attributeId): string {
        return $this->selectFirstField("
            SELECT frontend_label
              FROM eav_attribute
             WHERE attribute_id = $attributeId
        ");
    }

    public function getDefaultProductAttributeSetId(): int {
        return $this->getDefaultAttributeSetId('catalog_product_entity');
    }

    public function getDefaultCategoryAttributeSetId(): int {
        return $this->getDefaultAttributeSetId('catalog_category_entity');
    }

    private function getDefaultAttributeSetId(string $table): int {
        $entityTypeId = $this->getEntityTypeId($table);
        return $this->selectFirstField("
            SELECT attribute_set_id
              FROM eav_attribute_set
             WHERE entity_type_id = $entityTypeId
              AND attribute_set_name = 'Default'
        ");
    }

    public function getAttributeOptionId(string $attributeCode, string $attributeValueLabel): int {
        return $this->selectFirstField("
            SELECT v.option_id
              FROM eav_attribute_option_value v
              JOIN eav_attribute_option o ON o.option_id = v.option_id
              JOIN eav_attribute a ON a.attribute_id = o.attribute_id
             WHERE a.attribute_code = '$attributeCode'
               AND v.value = '$attributeValueLabel'
        ");
    }

    public function selectMaxId(string $table, string $idColumn): int {
        return $this->selectFirstField("SELECT MAX($idColumn) FROM $table");
    }

    public function deleteLastRow(string $table, string $idColumn): void {
        $this->execute("
            DELETE FROM $table
             ORDER BY $idColumn DESC
             LIMIT 1
        ");
    }
}
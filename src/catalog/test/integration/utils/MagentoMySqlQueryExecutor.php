<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;
use mysqli;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor as DB;

class MagentoMySqlQueryExecutor {

    private const SERVER_NAME = "127.0.0.1";

    // below settings as in magento/env/db.env file
    private const USER = "magento";
    private const PASSWORD = "magento";
    private const DB_NAME = "magento";

    /**
     * Returns the value of first field from first row, or null or error or no result data
     */
    public static function selectFirstField(string $selectQuery) {
        $connection = self::getConnection();

        try {
            $result = $connection->query($selectQuery);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_row();
                $result->free();
                return $row[0];
            } else {
                return null;
            }
        } finally {
            $connection->close();
        }
    }

    public static function executeAll(array $queries) {
        $connection = self::getConnection();

        try {
            foreach ($queries as $query) {
                $result = $connection->query($query);
                if (!$result) {
                    throw new Exception("Query $query failed: " . $connection->error);
                }
            }
        } finally {
            $connection->close();
        }
    }

    public static function execute(string $query) {
        self::executeAll([$query]);
    }

    private static function getConnection(): mysqli {
        return new mysqli(self::SERVER_NAME, self::USER, self::PASSWORD, self::DB_NAME);
    }

    public static function getProductId(string $productName): string {
        $productNameAttributeId = self::getProductNameAttributeId();

        return self::selectFirstField("
            SELECT entity_id
              FROM catalog_product_entity_varchar
             WHERE attribute_id = $productNameAttributeId
               AND value = '$productName'
        ");
    }

    public static function getCategoryId(string $categoryName): string {
        $categoryNameAttributeId = self::getCategoryNameAttributeId();

        return self::selectFirstField("
            SELECT entity_id
              FROM catalog_category_entity_varchar
             WHERE attribute_id = $categoryNameAttributeId
               AND value = '$categoryName'
        ");
    }

    public static function getProductAttributeId(string $attributeCode): string {
        $productEntityTypeId = self::getProductEntityTypeId();
        return self::getAttributeId($attributeCode, $productEntityTypeId);
    }

    public static function getCategoryAttributeId(string $attributeCode): string {
        $categoryEntityTypeId = self::getCategoryEntityTypeId();
        return self::getAttributeId($attributeCode, $categoryEntityTypeId);
    }

    public static function getProductNameAttributeId(): string {
        $productEntityTypeId = self::getProductEntityTypeId();
        return self::getNameAttributeId($productEntityTypeId);
    }

    public static function getCategoryNameAttributeId(): string {
        $categoryEntityTypeId = self::getCategoryEntityTypeId();
        return self::getNameAttributeId($categoryEntityTypeId);
    }

    public static function getProductEntityTypeId(): string {
        return self::getEntityTypeId('catalog_product_entity');
    }

    public static function getCategoryEntityTypeId(): string {
        return self::getEntityTypeId('catalog_category_entity');
    }

    private static function getEntityTypeId(string $table): string {
        return self::selectFirstField("
            SELECT entity_type_id
              FROM eav_entity_type
             WHERE entity_table = '$table'
        ");
    }

    public static function getNameAttributeId(int $entityTypeId): string {
        return self::getAttributeId('name', $entityTypeId);
    }

    public static function getAttributeId(string $attributeCode, int $entityTypeId): string {
        return self::selectFirstField("
            SELECT attribute_id
              FROM eav_attribute
             WHERE attribute_code = '$attributeCode'
               AND entity_type_id = $entityTypeId
        ");
    }

    public static function getAttributeDisplayName(int $attributeId): string {
        return self::selectFirstField("
            SELECT frontend_label
              FROM eav_attribute
             WHERE attribute_id = $attributeId
        ");
    }

    public static function getDefaultProductAttributeSetId(): int {
        return self::getDefaultAttributeSetId('catalog_product_entity');
    }

    public static function getDefaultCategoryAttributeSetId(): int {
        return self::getDefaultAttributeSetId('catalog_category_entity');
    }

    private static function getDefaultAttributeSetId(string $table): int {
        $entityTypeId = DB::getEntityTypeId($table);
        return DB::selectFirstField("
            SELECT attribute_set_id
              FROM eav_attribute_set
             WHERE entity_type_id = $entityTypeId
              AND attribute_set_name = 'Default'
        ");
    }
}
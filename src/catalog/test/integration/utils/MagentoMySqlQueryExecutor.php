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
        $productEntityTypeId = self::getEntityTypeId('catalog_product_entity');
        return self::getAttributeId($attributeCode, $productEntityTypeId);
    }

    public static function getProductNameAttributeId(): string {
        $productEntityTypeId = self::getEntityTypeId('catalog_product_entity');
        return self::getNameAttributeId($productEntityTypeId);
    }

    public static function getCategoryNameAttributeId(): string {
        $categoryEntityTypeId = self::getEntityTypeId('catalog_category_entity');
        return self::getNameAttributeId($categoryEntityTypeId);
    }

    public static function getEntityTypeId(string $table): string {
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
}
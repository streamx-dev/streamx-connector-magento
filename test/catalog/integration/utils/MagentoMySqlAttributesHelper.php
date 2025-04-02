<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

trait MagentoMySqlAttributesHelper {

    public function getIntProductAttributeValue(EntityIds $productId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): int {
        return $this->getAttributeValue('product', 'int', $productId, $attributeCode, $storeId);
    }
    public function getDecimalProductAttributeValue(EntityIds $productId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): float {
        return $this->getAttributeValue('product', 'decimal', $productId, $attributeCode, $storeId);
    }
    public function getVarcharProductAttributeValue(EntityIds $productId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): string {
        return $this->getAttributeValue('product', 'varchar', $productId, $attributeCode, $storeId);
    }
    public function getTextProductAttributeValue(EntityIds $productId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): string {
        return $this->getAttributeValue('product', 'text', $productId, $attributeCode, $storeId);
    }

    public function getIntCategoryAttributeValue(EntityIds $categoryId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): int {
        return $this->getAttributeValue('category', 'int', $categoryId, $attributeCode, $storeId);
    }
    public function getDecimalCategoryAttributeValue(EntityIds $categoryId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): float {
        return $this->getAttributeValue('category', 'decimal', $categoryId, $attributeCode, $storeId);
    }
    public function getVarcharCategoryAttributeValue(EntityIds $categoryId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): string {
        return $this->getAttributeValue('category', 'varchar', $categoryId, $attributeCode, $storeId);
    }
    public function getTextCategoryAttributeValue(EntityIds $categoryId, string $attributeCode, int $storeId = self::DEFAULT_STORE_ID): string {
        return $this->getAttributeValue('category', 'text', $categoryId, $attributeCode, $storeId);
    }

    public function insertIntProductAttribute(EntityIds $productId, int $attributeId, int $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('product', 'int', $productId, $attributeId, $attributeValue, $storeId);
    }
    public function insertDecimalProductAttribute(EntityIds $productId, int $attributeId, float $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('product', 'decimal', $productId, $attributeId, $attributeValue, $storeId);
    }
    public function insertVarcharProductAttribute(EntityIds $productId, int $attributeId, string $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('product', 'varchar', $productId, $attributeId, $attributeValue, $storeId);
    }
    public function insertTextProductAttribute(EntityIds $productId, int $attributeId, string $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('product', 'text', $productId, $attributeId, $attributeValue, $storeId);
    }

    public function insertIntCategoryAttribute(EntityIds $categoryId, int $attributeId, int $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('category', 'int', $categoryId, $attributeId, $attributeValue, $storeId);
    }
    public function insertDecimalCategoryAttribute(EntityIds $categoryId, int $attributeId, float $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('category', 'decimal', $categoryId, $attributeId, $attributeValue, $storeId);
    }
    public function insertVarcharCategoryAttribute(EntityIds $categoryId, int $attributeId, string $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('category', 'varchar', $categoryId, $attributeId, $attributeValue, $storeId);
    }
    public function insertTextCategoryAttribute(EntityIds $categoryId, int $attributeId, string $attributeValue, int $storeId = self::DEFAULT_STORE_ID): void {
        $this->insertAttribute('category', 'text', $categoryId, $attributeId, $attributeValue, $storeId);
    }

    public function deleteIntProductAttribute(EntityIds $productId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('product', 'int', $productId, $attributeId, $storeId);
    }
    public function deleteDecimalProductAttribute(EntityIds $productId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('product', 'decimal', $productId, $attributeId, $storeId);
    }
    public function deleteVarcharProductAttribute(EntityIds $productId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('product', 'varchar', $productId, $attributeId, $storeId);
    }
    public function deleteTextProductAttribute(EntityIds $productId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('product', 'text', $productId, $attributeId, $storeId);
    }

    public function deleteIntCategoryAttribute(EntityIds $categoryId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('category', 'int', $categoryId, $attributeId, $storeId);
    }
    public function deleteDecimalCategoryAttribute(EntityIds $categoryId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('category', 'decimal', $categoryId, $attributeId, $storeId);
    }
    public function deleteVarcharCategoryAttribute(EntityIds $categoryId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('category', 'varchar', $categoryId, $attributeId, $storeId);
    }
    public function deleteTextCategoryAttribute(EntityIds $categoryId, int $attributeId, int $storeId): void {
        $this->deleteAttribute('category', 'text', $categoryId, $attributeId, $storeId);
    }

    private function getAttributeValue(string $entityType, string $attributeType, EntityIds $entityIds, string $attributeCode, int $storeId) {
        $entityTypeId = $this->getEntityTypeId("catalog_{$entityType}_entity");
        $attributeId = $this->getAttributeId($attributeCode, $entityTypeId);
        $linkField = $this->entityAttributeLinkField;
        $entityTable = "catalog_{$entityType}_entity";
        $attributeTable = "catalog_{$entityType}_entity_$attributeType";
        return $this->selectSingleValue("
            SELECT attr.value
              FROM $entityTable category
              JOIN $attributeTable attr ON attr.$linkField = category.$linkField
             WHERE attr.attribute_id = $attributeId
               AND attr.$linkField = {$entityIds->getLinkFieldId()}
               AND attr.store_id = $storeId
         ");
    }

    private function insertAttribute(string $entityType, string $attributeType, EntityIds $entityId, int $attributeId, $attributeValue, int $storeId): void {
        $idColumn = $this->entityAttributeLinkField;
        $idValue = $entityId->getLinkFieldId();
        $attributeTable = "catalog_{$entityType}_entity_$attributeType";
        $this->execute("REPLACE INTO $attributeTable ($idColumn, attribute_id, store_id, value)
                                                    VALUES ($idValue, $attributeId, $storeId, '$attributeValue')");
    }

    private function deleteAttribute(string $entityType, string $attributeType, EntityIds $entityId, int $attributeId, int $storeId): void {
        $attributeTable = "catalog_{$entityType}_entity_$attributeType";
        $this->execute("
            DELETE FROM $attributeTable
             WHERE $this->entityAttributeLinkField = {$entityId->getLinkFieldId()}
               AND attribute_id = $attributeId
               AND store_id = $storeId
        ");
    }
}
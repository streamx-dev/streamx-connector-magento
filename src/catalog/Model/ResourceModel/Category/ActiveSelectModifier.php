<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Eav\Model\Entity\Attribute as Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ActiveSelectModifier implements SelectModifierInterface
{
    private LoadAttributes $loadAttributes;
    private CategoryMetaData $categoryMetadata;
    private ResourceConnection $resourceConnection;

    public function __construct(
        CategoryMetaData $metadataPool,
        LoadAttributes $loadAttributes,
        ResourceConnection $resourceConnection
    ) {
        $this->categoryMetadata = $metadataPool;
        $this->loadAttributes = $loadAttributes;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Process the select statement - filter categories by vendor
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function modify(Select $select, int $storeId): void
    {
        $linkField = $this->categoryMetadata->get()->getLinkField();

        $attribute = $this->getIsActiveAttribute();
        $checkSql = $this->getConnection()->getCheckSql('c.value_id > 0', 'c.value', 'd.value');
        $attributeId = (int) $attribute->getId();
        $backendTable = $this->resourceConnection->getTableName($attribute->getBackendTable());

        $joinCondition = [
            'd.attribute_id = ?',
            'd.store_id = 0',
            "d.$linkField = entity.$linkField",
        ];

        $defaultJoinCond = $this->getConnection()->quoteInto(
            implode(' AND ', $joinCondition),
            $attributeId
        );

        $storeJoinCond = [
            $this->getConnection()->quoteInto("c.attribute_id = ?", $attributeId),
            $this->getConnection()->quoteInto("c.store_id = ?", $storeId),
            "c.$linkField = entity.$linkField",
        ];

        $select->joinLeft(
            ['d' => $backendTable],
            $defaultJoinCond,
            []
        )->joinLeft(
            ['c' => $backendTable],
            implode(' AND ', $storeJoinCond),
            []
        )->where(sprintf("%s = 1", $checkSql));
    }

    /**
     * Retrieve Vendor Attribute
     *
     * @throws LocalizedException
     */
    private function getIsActiveAttribute(): Attribute
    {
        return $this->loadAttributes->getAttributeByCode('is_active');
    }

    /**
     * Retrieve Connection
     */
    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }
}

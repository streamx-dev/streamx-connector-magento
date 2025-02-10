<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

class StatusSelectModifier implements SelectModifierInterface
{
    private ResourceConnection $resourceConnection;
    private LoadAttributes $loadAttributes;
    private ProductMetaData $productMetaData;

    public function __construct(
        LoadAttributes $loadAttributes,
        ResourceConnection $resourceConnection,
        ProductMetaData $productMetaData
    ) {
        $this->loadAttributes = $loadAttributes;
        $this->resourceConnection = $resourceConnection;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @throws LocalizedException
     */
    public function modify(Select $select, int $storeId): void
    {
        $attribute = $this->getStatusAttribute();
        $backendTable = $this->resourceConnection->getTableName($attribute->getBackendTable());
        $checkSql = $this->getConnection()->getCheckSql('c.value_id > 0', 'c.value', 'd.value');

        $defaultJoinCond = $this->getDefaultJoinConditions();
        $storeJoinCond = $this->getStoreJoinConditions($storeId);

        $select->joinLeft(
            ['d' => $backendTable],
            $defaultJoinCond,
            []
        )->joinLeft(
            ['c' => $backendTable],
            $storeJoinCond,
            []
        )->where($checkSql . ' = ?', Status::STATUS_ENABLED);
    }

    /**
     * Retrieve Store join conditions
     *
     * @throws LocalizedException
     */
    private function getStoreJoinConditions(int $storeId): string
    {
        $linkFieldId = $this->productMetaData->get()->getLinkField();
        $attribute = $this->getStatusAttribute();
        $attributeId = (int) $attribute->getId();

        $storeJoinCond = [
            $this->getConnection()->quoteInto("c.attribute_id = ?", $attributeId),
            $this->getConnection()->quoteInto("c.store_id = ?", $storeId),
            sprintf('c.%s = %s.%s', $linkFieldId, Product::MAIN_TABLE_ALIAS, $linkFieldId),
        ];

        return implode(' AND ', $storeJoinCond);
    }

    /**
     * Get Default Join Conditions
     *
     * @throws LocalizedException
     */
    private function getDefaultJoinConditions(): string
    {
        $linkFieldId = $this->productMetaData->get()->getLinkField();
        $attribute = $this->getStatusAttribute();
        $attributeId = (int) $attribute->getId();

        $joinCondition = [
            'd.attribute_id = ?',
            'd.store_id = 0',
            sprintf('d.%s = %s.%s', $linkFieldId, Product::MAIN_TABLE_ALIAS, $linkFieldId),
        ];

        return $this->getConnection()->quoteInto(
            implode(' AND ', $joinCondition),
            $attributeId
        );
    }

    /**
     * Get Connection
     */
    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Get status attribute id
     *
     * @throws LocalizedException
     */
    private function getStatusAttribute(): Attribute
    {
        return $this->loadAttributes->getAttributeByCode('status');
    }
}

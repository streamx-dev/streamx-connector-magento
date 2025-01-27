<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\CompositeWithWebsiteModifier;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\DB\Select;

class Product
{
    /**
     * Alias for catalog_product_entity table
     */
    public const MAIN_TABLE_ALIAS = 'entity';

    private ResourceConnection $resourceConnection;
    private DbHelper $dbHelper;
    private CatalogConfig $productSettings;
    private ?array $configurableAttributeIds = null;
    private ProductMetaData $productMetaData;
    private CompositeWithWebsiteModifier $selectModifier;

    public function __construct(
        CatalogConfig $configSettings,
        CompositeWithWebsiteModifier $selectModifier,
        ResourceConnection $resourceConnection,
        ProductMetaData $productMetaData,
        DbHelper $dbHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dbHelper = $dbHelper;
        $this->productSettings = $configSettings;
        $this->selectModifier = $selectModifier;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @throws Exception
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getProducts(int $storeId = 1, array $productIds = [], int $fromId = 0, int $limit = 1000): array
    {
        $select = $this->prepareBaseProductSelect($this->getRequiredColumns(), $storeId);
        $this->addProductTypeFilter($select, $storeId);
        $tableName = self::MAIN_TABLE_ALIAS;

        if (!empty($productIds)) {
            $select->where(sprintf("%s.entity_id IN (?)", $tableName), $productIds);
        }

        $select->limit($limit);
        $select->where(sprintf("%s.entity_id > ?", $tableName), $fromId);
        $select->order(sprintf("%s.entity_id ASC", $tableName));

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function prepareBaseProductSelect(array $requiredColumns, int $storeId): Select
    {
        $select = $this->getConnection()->select()
            ->from(
                [self::MAIN_TABLE_ALIAS => $this->productMetaData->get()->getEntityTable()],
                $requiredColumns
            );

        $this->selectModifier->modify($select, $storeId);

        return $select;
    }

    private function getRequiredColumns(): array
    {
        $productMetaData = $this->productMetaData->get();
        $columns = [
            'entity_id',
            'type_id',
            'sku',
        ];

        $linkField = $productMetaData->getLinkField();

        if ($productMetaData->getIdentifierField() !== $linkField) {
            $columns[] = $linkField;
        }

        return $columns;
    }

    /**
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function loadChildrenProducts(array $parentIds, int $storeId): array
    {
        $linkField = $this->productMetaData->get()->getLinkField();
        $entityId = $this->productMetaData->get()->getIdentifierField();
        $columns = [
            'sku',
            $entityId,
        ];

        if ($linkField !== $entityId) {
            $columns[] = $linkField;
        }

        $select = $this->prepareBaseProductSelect($columns, $storeId);

        $select->join(
            ['link_table' => $this->resourceConnection->getTableName('catalog_product_super_link')],
            sprintf('link_table.product_id = %s.entity_id', self::MAIN_TABLE_ALIAS),
            []
        );

        $select->where('link_table.parent_id IN (?)', $parentIds);
        $select->group('entity_id');

        $this->dbHelper->addGroupConcatColumn($select, 'parent_ids', 'parent_id');

        return $this->getConnection()->fetchAll($select);
    }

    private function addProductTypeFilter(Select $select, int $storeId): void
    {
        $types = $this->productSettings->getAllowedProductTypes($storeId);
        $select->where(sprintf('%s.type_id IN (?)', self::MAIN_TABLE_ALIAS), $types);
    }

    /**
     * @throws Exception
     */
    public function getRelationsByChild(array $childrenIds): array
    {
        $metadata = $this->productMetaData->get();
        $linkFieldId = $metadata->getLinkField();
        $entityTable = $this->resourceConnection->getTableName($metadata->getEntityTable());
        $relationTable = $this->resourceConnection->getTableName(('catalog_product_relation'));
        $joinCondition = "relation.parent_id = entity.$linkFieldId";

        $select = $this->getConnection()->select()
            ->from(['relation' => $relationTable], [])
            ->join(['entity' => $entityTable], $joinCondition, [$metadata->getIdentifierField()])
            ->where('child_id IN(?)', array_map('intval', $childrenIds));

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Get list of attribute ids used to create configurable products
     */
    public function getConfigurableAttributeIds(): array
    {
        if (null === $this->configurableAttributeIds) {
            $select = $this->getConnection()->select();
            $select->from(
                $this->resourceConnection->getTableName('catalog_product_super_attribute'),
                ['attribute_id']
            );

            $select->distinct();

            $this->configurableAttributeIds = $this->getConnection()->fetchCol($select);
        }

        return $this->configurableAttributeIds;
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }
}

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
     * @return array<int,array<int>> key: child ID, value: parent ID(s). Returns data only for those of the products that have parent of type "Configurable Product"
     * @throws Exception
     */
    public function getParentsForProductVariants(array $productIds): array
    {
        /** The full query to load parent IDs for products (community DB version) is:
         * SELECT
         *        child.entity_id AS child_id,
         *        child.type_id AS child_type,
         *        child.sku AS child_sku,
         *        parent.entity_id AS parent_id,
         *        parent.type_id AS parent_type,
         *        parent.sku AS parent_sku
         *   FROM catalog_product_entity child
         *   JOIN catalog_product_relation relation ON relation.child_id = child.entity_id
         *   JOIN catalog_product_entity parent ON parent.entity_id = relation.parent_id
         *  WHERE parent.type_id = 'configurable'
         *  ORDER BY child_id, parent_id
         */
        $metadata = $this->productMetaData->get();
        $linkFieldId = $metadata->getLinkField();
        $productIdField = $metadata->getIdentifierField();
        $entityTable = $this->resourceConnection->getTableName($metadata->getEntityTable());
        $relationTable = $this->resourceConnection->getTableName('catalog_product_relation');

        $select = $this->getConnection()->select()
            ->from(['child' => $entityTable], ['child_id' => $productIdField])
            ->join(['relation' => $relationTable], "relation.child_id = child.$linkFieldId", ['parent_id'])
            ->join(['parent' => $entityTable], "relation.parent_id = parent.$linkFieldId", [])
            ->where('parent.type_id = ?', 'configurable')
            ->where('relation.child_id IN(?)', array_map('intval', $productIds));

        $rows = $this->getConnection()->fetchAll($select);

        $resultMap = [];
        foreach ($rows as $row) {
            $childId = (int) $row['child_id'];
            $parentId = (int) $row['parent_id'];
            $resultMap[$childId][] = $parentId;
        }
        return $resultMap;
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

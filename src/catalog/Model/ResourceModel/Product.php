<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\StatusEnabledSelectModifier;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\CurrentWebsiteSelectModifier;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\VisibleSelectModifier;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\DB\Select;
use Zend_Db_Expr;
use Zend_Db_Select_Exception;

class Product
{
    private const PRODUCT_ATTRIBUTE_TABLES = [
        'catalog_product_entity_datetime',
        'catalog_product_entity_decimal',
        'catalog_product_entity_gallery',
        'catalog_product_entity_int',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar'
    ];

    private ResourceConnection $resourceConnection;
    private DbHelper $dbHelper;
    private CatalogConfig $productSettings;
    private ?array $configurableAttributeIds = null;
    private ProductMetaData $productMetaData;
    private CompositeSelectModifier $mainProductSelectModifier;
    private CompositeSelectModifier $childProductSelectModifier;

    public function __construct(
        CatalogConfig $configSettings,
        CurrentWebsiteSelectModifier $currentWebsiteSelectModifier,
        StatusEnabledSelectModifier $statusEnabledSelectModifier,
        VisibleSelectModifier $visibleSelectModifier,
        ResourceConnection $resourceConnection,
        ProductMetaData $productMetaData,
        DbHelper $dbHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dbHelper = $dbHelper;
        $this->productSettings = $configSettings;
        $this->mainProductSelectModifier = new CompositeSelectModifier($currentWebsiteSelectModifier, $statusEnabledSelectModifier, $visibleSelectModifier);
        $this->childProductSelectModifier = new CompositeSelectModifier($currentWebsiteSelectModifier, $statusEnabledSelectModifier); // load child products even if configured as not visible
        $this->productMetaData = $productMetaData;
    }

    /**
     * @throws Exception
     */
    public function getProducts(int $storeId, array $productIds, int $fromId, int $limit = 1000): array
    {
        $entityIdColumn = "entity.entity_id";

        $select = $this
            ->prepareProductSelect($this->getRequiredColumns(), $storeId)
            ->where("$entityIdColumn > ?", $fromId)
            ->limit($limit);

        if (!empty($productIds)) {
            $select->where("$entityIdColumn IN (?)", $productIds);
        }

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @return int[]
     */
    public function getAllProductIds(int $storeId): array
    {
        $select = $this->prepareProductSelect(['entity_id'], $storeId);
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Prepares product select for selecting main products
     */
    private function prepareProductSelect(array $columns, int $storeId): Select {
        $select = $this->prepareBaseProductSelect($columns, $storeId, $this->mainProductSelectModifier);
        $this->addProductTypeFilter($select);
        $select->order('entity.entity_id ASC');
        return $select;
    }

    /**
     * Prepares base product select for selecting main or variant products
     */
    private function prepareBaseProductSelect(array $requiredColumns, int $storeId, CompositeSelectModifier $selectModifier): Select
    {
        $select = $this->getConnection()->select()
            ->from(
                ['entity' => $this->productMetaData->get()->getEntityTable()],
                $requiredColumns
            );

        $selectModifier->modifyAll($select, $storeId);

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

        $select = $this->prepareBaseProductSelect($columns, $storeId, $this->childProductSelectModifier);

        $select->join(
            ['link_table' => $this->resourceConnection->getTableName('catalog_product_super_link')],
            'link_table.product_id = entity.entity_id',
            []
        );

        $select->where('link_table.parent_id IN (?)', $parentIds);
        $select->group('entity_id');

        $this->dbHelper->addGroupConcatColumn($select, 'parent_ids', 'parent_id');

        return $this->getConnection()->fetchAll($select);
    }

    private function addProductTypeFilter(Select $select): void
    {
        $types = $this->productSettings->getAllowedProductTypes();
        $select->where('entity.type_id IN (?)', $types);
    }

    /**
     * @param int[] $attributeIds
     * @return int[]
     * @throws Zend_Db_Select_Exception
     */
    // TODO add conditions for products only from current website / active / visible
    public function loadIdsOfProductsThatUseAttributes(array $attributeIds): array
    {
        if (empty($attributeIds)) {
            return [];
        }

        $linkField = $this->productMetaData->get()->getLinkField();
        $connection = $this->resourceConnection->getConnection();

        $selectProductIdsQueries = [];
        foreach (self::PRODUCT_ATTRIBUTE_TABLES as $table) {
            // select values of all [row_id] or [entity_id] from each product attributes table
            $selectProductIdsQueries[] = $connection->select()
                ->from($this->resourceConnection->getTableName($table), [$linkField])
                ->distinct()
                ->where('attribute_id IN(?)', $attributeIds);
        }

        // union results of all the selects
        $selectProductIdsUnionQuery = $connection->select()->union($selectProductIdsQueries);

        // select actual entity_ids from main products table, that have the product ids found in all the product attribute tables
        $selectProductEntityIds = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id'])
            ->distinct()
            ->where("$linkField IN(?)", new Zend_Db_Expr($selectProductIdsUnionQuery))
            ->order('entity_id');

        return $connection->fetchCol($selectProductEntityIds);
    }

    /**
     * @param int[] $productIds
     * @return int[]
     */
    public function retrieveAllVariantParentAndChildIds(array $productIds): array
    {
        // TODO add conditions for products only from current website / active / visible
        /** The full query to load all parent and child IDs for the given products (community DB version) is:
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
         *    AND (child.entity_id IN ($productIds) OR parent.entity_id IN ($productIds))
         *  ORDER BY child_id, parent_id
         */
        $metadata = $this->productMetaData->get();
        $linkFieldId = $metadata->getLinkField();
        $productIdField = $metadata->getIdentifierField();
        $entityTable = $this->resourceConnection->getTableName($metadata->getEntityTable());
        $relationTable = $this->resourceConnection->getTableName('catalog_product_relation');
        $productIdsString = implode(',', $productIds);

        $select = $this->getConnection()->select()
            ->from(['child' => $entityTable], ['child_id' => $productIdField])
            ->join(['relation' => $relationTable], "relation.child_id = child.$linkFieldId", ['parent_id'])
            ->join(['parent' => $entityTable], "relation.parent_id = parent.$linkFieldId", [])
            ->where(
                sprintf('%s AND (%s OR %s)',
                    "parent.type_id = 'configurable'",
                    "relation.parent_id IN($productIdsString)",
                    "relation.child_id IN($productIdsString)"
            ));

        $rows = $this->getConnection()->fetchAll($select);

        $resultIds = [];
        foreach ($rows as $row) {
            $resultIds[] = (int) $row['child_id'];
            $resultIds[] = (int) $row['parent_id'];
        }
        return array_unique($resultIds);
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

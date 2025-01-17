<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\BaseSelectModifierInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Category as CoreCategoryModel;
use Magento\Framework\DB\Select;

class Category
{
    /**
     * Alias form category entity table
     */
    private const MAIN_TABLE_ALIAS = 'entity';

    private ResourceConnection $resource;
    private BaseSelectModifierInterface $baseSelectModifier;
    private CategoryMetaData $categoryMetaData;

    public function __construct(
        BaseSelectModifierInterface $baseSelectModifier,
        ResourceConnection $resourceConnection,
        CategoryMetaData $categoryMetaData
    ) {
        $this->resource = $resourceConnection;
        $this->categoryMetaData = $categoryMetaData;
        $this->baseSelectModifier = $baseSelectModifier;
    }

    /**
     * @throws Exception
     */
    public function getCategories(int $storeId = 1, array $categoryIds = [], int $fromId = 0, int $limit = 1000): array
    {
        $metaData = $this->categoryMetaData->get();
        $select = $this->getConnection()
            ->select()
            ->from(
                [self::MAIN_TABLE_ALIAS => $metaData->getEntityTable()],
                ['parent_id', 'path']
            )->columns(
                ['id' => 'entity_id']
            );

        $select = $this->filterByStore($select, $storeId);
        $tableName = self::MAIN_TABLE_ALIAS;

        if (!empty($categoryIds)) {
            $select->where(sprintf("%s.entity_id IN (?)", $tableName), $categoryIds);
        }

        $select->where(sprintf("%s.entity_id > ?", $tableName), $fromId);
        $select->limit($limit);
        $select->order(sprintf("%s.entity_id ASC", $tableName));

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @throws Exception
     */
    public function getCategoryProductSelect(int $storeId, array $productIds): array
    {
        $metaData = $this->categoryMetaData->get();
        $select = $this->getConnection()->select()->from(
            [self::MAIN_TABLE_ALIAS => $metaData->getEntityTable()]
        );

        $select = $this->filterByStore($select, $storeId);
        $table = $this->resource->getTableName('catalog_category_product');
        $entityIdField = $this->categoryMetaData->get()->getIdentifierField();
        $select->reset(Select::COLUMNS);
        $select->joinInner(
            ['cpi' => $table],
            self::MAIN_TABLE_ALIAS . ".$entityIdField = cpi.category_id",
            [
                'category_id',
                'product_id',
                'position',
            ]
        )->where('cpi.product_id IN (?)', $productIds);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @throws Exception
     */
    public function getParentIds(array $categoryIds): array
    {
        $metaData = $this->categoryMetaData->get();
        $entityField = $metaData->getIdentifierField();

        $select = $this->getConnection()->select()->from(
            [self::MAIN_TABLE_ALIAS => $metaData->getEntityTable()],
            ['path']
        );

        $select->where(
            "$entityField IN (?)",
            array_map('intval', $categoryIds)
        );

        $paths = $this->getConnection()->fetchCol($select);
        $parentIds = [];

        foreach ($paths as $path) {
            $path = explode('/', $path);

            foreach ($path as $catId) {
                $catId = (int)$catId;

                if ($catId !== CoreCategoryModel::TREE_ROOT_ID) {
                    $parentIds[] = $catId;
                }
            }
        }

        return array_unique($parentIds);
    }

    /**
     * @return int[]
     * @throws Exception
     */
    public function getAllSubCategories(int $categoryId): array
    {
        $metaData = $this->categoryMetaData->get();
        $entityField = $metaData->getIdentifierField();
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            [self::MAIN_TABLE_ALIAS => $metaData->getEntityTable()],
            [$entityField]
        );

        $catIdExpr = $connection->quote("%/{$categoryId}/%");
        $select->where("path like {$catIdExpr}");

        return $connection->fetchCol($select);
    }

    /**
     * @throws Exception
     * @throws NoSuchEntityException
     */
    private function filterByStore(Select $select, int $storeId): Select
    {
        return $this->baseSelectModifier->execute($select, (int) $storeId);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

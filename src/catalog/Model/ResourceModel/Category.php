<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\Category as CoreCategoryModel;
use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\EligibleCategorySelectModifier;
use Zend_Db_Select;

class Category
{
    private ResourceConnection $resource;
    private EligibleCategorySelectModifier $eligibleCategorySelectModifier;
    private CategoryMetaData $categoryMetaData;

    public function __construct(
        EligibleCategorySelectModifier $eligibleCategorySelectModifier,
        ResourceConnection $resourceConnection,
        CategoryMetaData $categoryMetaData
    ) {
        $this->resource = $resourceConnection;
        $this->categoryMetaData = $categoryMetaData;
        $this->eligibleCategorySelectModifier = $eligibleCategorySelectModifier;
    }

    /**
     * @throws Exception
     */
    public function getCategories(int $storeId, array $categoryIds = [], int $fromId = 0, int $limit = 1000): array
    {
        $select = $this->getCategoriesBaseSelect($storeId);
        $this->eligibleCategorySelectModifier->modify($select, $storeId);

        if (!empty($categoryIds)) {
            $select->where("entity.entity_id IN (?)", $categoryIds);
        }

        $select->where("entity.entity_id > ?", $fromId);
        $select->limit($limit);
        $select->order("entity.entity_id ASC");

        $categories = $this->getConnection()->fetchAll($select);
        $this->addParents($categories, $storeId);

        return $categories;
    }

    private function addParents(array &$categories, int $storeId): void
    {
        $allParentCategoryIds = $this->collectAllParentCategoryIds($categories);
        if (empty($allParentCategoryIds)) {
            return;
        }

        $parentCategoriesById = $this->loadCategoriesMapById($storeId, $allParentCategoryIds);

        foreach ($categories as &$category) {
            $categoryIds = explode('/', $category['path']);
            array_pop($categoryIds); // remove own ID (last element of path)
            $this->addParent($category, $categoryIds, $parentCategoriesById);
        }
    }

    private function addParent(array &$category, array $categoryIds, array $categoriesById): void
    {
        if (!empty($categoryIds)) {
            $category['parent'] = $categoriesById[array_pop($categoryIds)]; // last item in path is the direct parent ID
            $this->addParent($category['parent'], $categoryIds, $categoriesById);
        }
    }

    private function collectAllParentCategoryIds(array $categories): array
    {
        $allParentCategoryIds = [];
        foreach ($categories as $category) {
            $categoryIds = explode('/', $category['path']);
            array_pop($categoryIds); // leave only parents
            array_push($allParentCategoryIds, ...$categoryIds);
        }
        return array_unique($allParentCategoryIds);
    }

    private function loadCategoriesMapById(int $storeId, array $categoryIds): array
    {
        $select = $this->getCategoriesBaseSelect($storeId)
            ->where("entity.entity_id IN (?)", $categoryIds);
        $categories = $this->getConnection()->fetchAll($select);

        $categoriesById = [];
        foreach ($categories as $category) {
            $categoriesById[$category['id']] = $category;
        }
        return $categoriesById;
    }

    /**
     * @return array<int, array<int>> key = product id, value = array of the product's category ids
     * @throws Exception
     */
    public function getProductCategoriesMap(int $storeId, array $productIds): array
    {
        $select = $this->getConnection()->select()->from(
            ['entity' => $this->categoryMetaData->getEntityTable()]
        );

        $this->eligibleCategorySelectModifier->modify($select, $storeId);
        $table = $this->resource->getTableName('catalog_category_product');
        $entityIdField = $this->categoryMetaData->getEntityIdField();
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->joinInner(
            ['cpi' => $table],
            "entity.$entityIdField = cpi.category_id",
            [
                'category_id',
                'product_id'
            ]
        )->where('cpi.product_id IN (?)', $productIds);

        $rows = $this->getConnection()->fetchAll($select);
        return $this->toProductCategoriesMap($rows);
    }

    private function toProductCategoriesMap(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            $productId = (int) $row['product_id'];
            $result[$productId][] = $categoryId;
        }
        return $result;
    }

    /**
     * Returns set of IDs of all parent categories (parent, grandparent etc.) for each of the input categories
     * @throws Exception
     */
    public function getParentIds(array $categoryIds): array
    {
        $entityField = $this->categoryMetaData->getEntityIdField();

        $select = $this->getConnection()->select()->from(
            ['entity' => $this->categoryMetaData->getEntityTable()],
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
        $entityField = $this->categoryMetaData->getEntityIdField();
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['entity' => $this->categoryMetaData->getEntityTable()],
            [$entityField]
        );

        $catIdExpr = $connection->quote("%/$categoryId/%");
        $select->where("path like $catIdExpr");

        return $connection->fetchCol($select);
    }

    public function getCategoriesBaseSelect(int $storeId): Select
    {
        $entityTable = $this->categoryMetaData->getEntityTable();

        $select = $this->getConnection()
            ->select()
            ->from(['entity' => $entityTable], [
                'parent_id',
                'path',
                'id' => 'entity_id'
            ]);

        // select category name (higher priority for store-level name)
        $this->joinAttributesTable($select, 'name', 'name_attr');
        $this->joinAttributeValuesTable($select, 'default_name', 'name_attr', 0);
        $this->joinAttributeValuesTable($select, 'store_name', 'name_attr', $storeId);
        $select->columns(['name' => 'COALESCE(store_name.value, default_name.value)']);

        // select url key (higher priority for store-level key)
        $this->joinAttributesTable($select, 'url_key', 'url_key_attr');
        $this->joinAttributeValuesTable($select, 'default_url_key', 'url_key_attr', 0);
        $this->joinAttributeValuesTable($select, 'store_url_key', 'url_key_attr', $storeId);
        $select->columns(['url_key' => 'COALESCE(store_url_key.value, default_url_key.value)']);

        return $select;
    }

    private function joinAttributesTable(Select $select, string $attributeCode, string $tableAlias): void
    {
        $entityTypeId = $this->categoryMetaData->getEntityTypeId();
        $select->joinLeft(
            [$tableAlias => $this->resource->getTableName('eav_attribute')],
            "$tableAlias.attribute_code = '$attributeCode'
                AND $tableAlias.entity_type_id = $entityTypeId",
            []
        );
    }

    private function joinAttributeValuesTable(Select $select, string $tableAlias, string $attributesTableAlias, int $storeId): void
    {
        $linkField = $this->categoryMetaData->getLinkField();
        $select->joinLeft(
            [$tableAlias => $this->resource->getTableName('catalog_category_entity_varchar')],
            "$tableAlias.$linkField = entity.$linkField
                AND $tableAlias.attribute_id = $attributesTableAlias.attribute_id
                AND $tableAlias.store_id = $storeId",
            []
        );
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

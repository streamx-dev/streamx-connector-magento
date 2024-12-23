<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Category
{
    private ResourceConnection $resource;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private \StreamX\ConnectorCatalog\Model\ResourceModel\Category $categoryResourceModel;

    /**
     * @var array Local cache for category names
     */
    private array $categoryNameCache = [];

    private CategoryMetaData $categoryMetaData;

    public function __construct(
        ResourceConnection $resourceModel,
        CategoryMetaData $categoryMetaData,
        \StreamX\ConnectorCatalog\Model\ResourceModel\Category $categoryResourceModel,
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->resource = $resourceModel;
        $this->categoryMetaData = $categoryMetaData;
        $this->categoryResourceModel = $categoryResourceModel;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function loadCategoryData(int $storeId, array $productIds): array
    {
        $categoryData = $this->categoryResourceModel->getCategoryProductSelect($storeId, $productIds);
        $categoryIds = [];

        foreach ($categoryData as $categoryDataRow) {
            $categoryIds[] = $categoryDataRow['category_id'];
        }

        $storeCategoryName = $this->loadCategoryNames(array_unique($categoryIds), $storeId);

        foreach ($categoryData as &$categoryDataRow) {
            $categoryDataRow['name'] = '';
            if (isset($storeCategoryName[(int) $categoryDataRow['category_id']])) {
                $categoryDataRow['name'] = $storeCategoryName[(int) $categoryDataRow['category_id']];
            }
        }

        return $categoryData;
    }

    /**
     * @return array|mixed
     * @throws LocalizedException
     */
    private function loadCategoryNames(array $categoryIds, int $storeId)
    {
        $loadCategoryIds = $categoryIds;

        if (isset($this->categoryNameCache[$storeId])) {
            $loadCategoryIds = array_diff($categoryIds, array_keys($this->categoryNameCache[$storeId]));
        }

        $loadCategoryIds = array_map('intval', $loadCategoryIds);

        if (!empty($loadCategoryIds)) {
            $categoryName = $this->loadCategoryName($loadCategoryIds, $storeId);

            foreach ($categoryName as $row) {
                $categoryId = (int)$row['entity_id'];
                $this->categoryNameCache[$storeId][$categoryId] = $row['name'];
            }
        }

        return $this->categoryNameCache[$storeId] ?? [];
    }

    /**
     * @throws LocalizedException
     */
    private function loadCategoryName(array $loadCategoryIds, int $storeId): array
    {
        /** @var CategoryCollection $categoryCollection */
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->setStoreId($storeId);
        $categoryCollection->setStore($storeId);
        $categoryCollection->addFieldToFilter('entity_id', ['in' => $loadCategoryIds]);

        $linkField = $this->categoryMetaData->get()->getLinkField();
        $categoryCollection->joinAttribute('name', 'catalog_category/name', $linkField);

        $select = $categoryCollection->getSelect();

        return $this->getConnection()->fetchAll($select);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

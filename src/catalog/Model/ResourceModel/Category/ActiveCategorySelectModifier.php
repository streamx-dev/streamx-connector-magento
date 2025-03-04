<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use DomainException;
use Exception;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ActiveCategorySelectModifier implements SelectModifierInterface
{
    private CategoryMetaData $categoryMetadata;
    private ResourceConnection $resourceConnection;
    private int $isActiveAttributeId;
    private string $isActiveAttributeBackendTable;

    public function __construct(
        CategoryMetaData $metadataPool,
        ResourceConnection $resourceConnection,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->categoryMetadata = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->loadIsActiveAttribute($attributeCollectionFactory);
    }

    /**
     * Process the select statement - filter categories to select only active categories
     * @throws Exception
     */
    public function modify(Select $select, int $storeId): void
    {
        $linkField = $this->categoryMetadata->getLinkField();
        $backendTable = $this->resourceConnection->getTableName($this->isActiveAttributeBackendTable);

        $select->joinLeft(
            ['d' => $backendTable],
            "d.attribute_id = $this->isActiveAttributeId AND d.store_id = 0 AND d.$linkField = entity.$linkField",
            []
        )->joinLeft(
            ['c' => $backendTable],
            "c.attribute_id = $this->isActiveAttributeId AND c.store_id = $storeId AND c.$linkField = entity.$linkField",
            []
        )->where("CASE WHEN c.value_id > 0 THEN c.value = 1 ELSE d.value = 1 END");
    }

    private function loadIsActiveAttribute(CollectionFactory $attributeCollectionFactory): void
    {
        $attributeCollection = $attributeCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_code', 'is_active')
            ->setPageSize(1);

        foreach ($attributeCollection as $attribute) {
            $this->isActiveAttributeId = (int) $attribute->getId();
            $this->isActiveAttributeBackendTable = $attribute->getBackendTable();
            return;
        }

        throw new DomainException("Cannot load is_active attribute");
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use DomainException;
use Exception;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ActiveCategorySelectModifier implements SelectModifierInterface
{
    private CategoryMetaData $categoryMetadata;
    private ResourceConnection $resourceConnection;
    private Attribute $isActiveAttribute;

    public function __construct(
        CategoryMetaData $metadataPool,
        ResourceConnection $resourceConnection,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->categoryMetadata = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->isActiveAttribute = $this->loadIsActiveAttribute($attributeCollectionFactory);
    }

    /**
     * Process the select statement - filter categories to select only active categories
     * @throws Exception
     */
    public function modify(Select $select, int $storeId): void
    {
        $linkField = $this->categoryMetadata->get()->getLinkField();
        $connection = $this->resourceConnection->getConnection();
        $backendTable = $this->resourceConnection->getTableName($this->isActiveAttribute->getBackendTable());

        $select->joinLeft(
            ['d' => $backendTable],
            $connection->quoteInto(
                "d.attribute_id = ? AND d.store_id = 0 AND d.$linkField = entity.$linkField",
                $this->isActiveAttribute->getAttributeId()
            ),
            []
        )->joinLeft(
            ['c' => $backendTable],
            $connection->quoteInto(
                "c.attribute_id = ? AND c.store_id = ? AND c.$linkField = entity.$linkField",
                $this->isActiveAttribute->getAttributeId(), $storeId
            ),
            []
        )->where("CASE WHEN c.value_id > 0 THEN c.value = 1 ELSE d.value = 1 END");
    }

    private function loadIsActiveAttribute(CollectionFactory $attributeCollectionFactory): Attribute
    {
        $attributeCollection = $attributeCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_code', 'is_active')
            ->setPageSize(1);

        foreach ($attributeCollection as $attribute) {
            return $attribute;
        }

        throw new DomainException("Cannot load is_active attribute");
    }
}

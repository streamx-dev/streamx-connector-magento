<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Eav\Model\Entity\Attribute as Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class ActiveSelectModifier implements SelectModifierInterface
{
    private CategoryMetaData $categoryMetadata;
    private ResourceConnection $resourceConnection;
    private CollectionFactory $attributeCollectionFactory;
    private ?Attribute $isActiveAttribute = null;

    public function __construct(
        CategoryMetaData $metadataPool,
        ResourceConnection $resourceConnection,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->categoryMetadata = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
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
        $isActiveAttribute = $this->getIsActiveAttribute();

        $connection = $this->resourceConnection->getConnection();
        $isActiveAttributeId = (int) $isActiveAttribute->getId();
        $backendTable = $this->resourceConnection->getTableName($isActiveAttribute->getBackendTable());

        $select->joinLeft(
            ['d' => $backendTable],
            $connection->quoteInto(
                "d.attribute_id = ? AND d.store_id = 0 AND d.$linkField = entity.$linkField",
                $isActiveAttributeId
            ),
            []
        )->joinLeft(
            ['c' => $backendTable],
            $connection->quoteInto(
                "c.attribute_id = ? AND c.store_id = ? AND c.$linkField = entity.$linkField",
                $isActiveAttributeId, $storeId
            ),
            []
        )->where("CASE WHEN c.value_id > 0 THEN c.value = 1 ELSE d.value = 1 END");
    }

    private function getIsActiveAttribute(): Attribute
    {
        if (null === $this->isActiveAttribute) {
            $attributeCollection = $this->attributeCollectionFactory
                ->create()
                ->addFieldToFilter('attribute_code', 'is_active');

            foreach ($attributeCollection as $attribute) {
                $this->isActiveAttribute = $attribute;
                break;
            }
        }

        return $this->isActiveAttribute;
    }
}

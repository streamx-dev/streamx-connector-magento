<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Model\ResourceModel\AbstractEavAttributes;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Category Attributes Provider
 */
class AttributeDataProvider extends AbstractEavAttributes
{
    private LoadAttributes $loadAttributes;

    public function __construct(
        LoadAttributes $loadAttributes,
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        $entityType = CategoryInterface::class
    ) {
        $this->loadAttributes = $loadAttributes;

        parent::__construct($resourceConnection, $metadataPool, $entityType);
    }

    /**
     * @throws LocalizedException
     */
    public function getAttributeByCode(string $attributeCode): Attribute
    {
        return $this->loadAttributes->getAttributeByCode($attributeCode);
    }

    /**
     * @return Attribute[]
     */
    public function initAttributes()
    {
        return $this->loadAttributes->execute();
    }
}

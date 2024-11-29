<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\Category as CategoryMapping;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\AbstractEavAttributes;
use Divante\VsbridgeIndexerCore\Api\ConvertValueInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Category Attributes Provider
 */
class AttributeDataProvider extends AbstractEavAttributes
{

    /**
     * @var LoadAttributes
     */
    private $loadAttributes;

    public function __construct(
        LoadAttributes $loadAttributes,
        CategoryMapping $categoryMapping,
        ResourceConnection $resourceConnection,
        ConvertValueInterface $castValue,
        MetadataPool $metadataPool,
        $entityType = \Magento\Catalog\Api\Data\CategoryInterface::class
    ) {
        $this->loadAttributes = $loadAttributes;

        parent::__construct($resourceConnection, $metadataPool, $castValue, $categoryMapping, $entityType);
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
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

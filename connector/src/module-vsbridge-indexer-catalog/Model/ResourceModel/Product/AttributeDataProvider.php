<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerCore\Api\ConvertValueInterface;
use Divante\VsbridgeIndexerCatalog\Index\Mapping\Product as ProductMapping;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\AbstractEavAttributes;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Eav\Model\Entity\Attribute;

/**
 * Products Attribute provider
 */
class AttributeDataProvider extends AbstractEavAttributes
{

    /**
     * @var LoadAttributes
     */
    private $loadAttributes;

    /**
     * AttributeDataProvider constructor.
     *
     * @param LoadAttributes $loadAttributes
     * @param ProductMapping $productMapping
     * @param ResourceConnection $resourceConnection
     * @param ConvertValueInterface $convertValue
     * @param MetadataPool $metadataPool
     * @param string $entityType
     */
    public function __construct(
        LoadAttributes $loadAttributes,
        ProductMapping $productMapping,
        ResourceConnection $resourceConnection,
        ConvertValueInterface $convertValue,
        MetadataPool $metadataPool,
        $entityType = \Magento\Catalog\Api\Data\ProductInterface::class
    ) {
        $this->loadAttributes = $loadAttributes;
        parent::__construct($resourceConnection, $metadataPool, $convertValue, $productMapping, $entityType);
    }

    /**
     * @return Attribute[]
     */
    public function getAttributesById()
    {
        return $this->initAttributes();
    }

    /**
     * @param int $attributeId
     *
     * @return Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeById($attributeId)
    {
        return $this->loadAttributes->getAttributeById((int) $attributeId);
    }

    /**
     * @param string $attributeCode
     *
     * @return Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeByCode($attributeCode)
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

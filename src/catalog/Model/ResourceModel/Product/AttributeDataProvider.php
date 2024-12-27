<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCore\Api\ConvertValueInterface;
use StreamX\ConnectorCatalog\Index\Mapping\Product as ProductMapping;
use StreamX\ConnectorCatalog\Model\ResourceModel\AbstractEavAttributes;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Eav\Model\Entity\Attribute;

/**
 * Products Attribute provider
 */
class AttributeDataProvider extends AbstractEavAttributes
{
    private LoadAttributes $loadAttributes;

    public function __construct(
        LoadAttributes $loadAttributes,
        ProductMapping $productMapping,
        ResourceConnection $resourceConnection,
        ConvertValueInterface $convertValue,
        MetadataPool $metadataPool,
        $entityType = ProductInterface::class
    ) {
        $this->loadAttributes = $loadAttributes;
        parent::__construct($resourceConnection, $metadataPool, $convertValue, $productMapping, $entityType);
    }

    /**
     * @throws LocalizedException
     */
    public function getAttributeById(int $attributeId): Attribute
    {
        return $this->loadAttributes->getAttributeById($attributeId);
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

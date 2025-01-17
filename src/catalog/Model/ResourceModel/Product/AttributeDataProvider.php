<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Model\ResourceModel\AbstractEavAttributes;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Eav\Model\Entity\Attribute;

/**
 * Products Attribute provider
 */
// TODO merge with base class - this is the only extending class
class AttributeDataProvider extends AbstractEavAttributes
{
    private LoadAttributes $loadAttributes;

    public function __construct(
        LoadAttributes $loadAttributes,
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        $entityType = ProductInterface::class
    ) {
        $this->loadAttributes = $loadAttributes;
        parent::__construct($resourceConnection, $metadataPool, $entityType);
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

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Index\Mapping\NoOpMapping;
use StreamX\ConnectorCatalog\Model\ResourceModel\AbstractEavAttributes;
use StreamX\ConnectorCore\Api\ConvertValueInterface;
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
        NoOpMapping $noOpMapping,
        ResourceConnection $resourceConnection,
        ConvertValueInterface $castValue,
        MetadataPool $metadataPool,
        $entityType = CategoryInterface::class
    ) {
        $this->loadAttributes = $loadAttributes;

        parent::__construct($resourceConnection, $metadataPool, $castValue, $noOpMapping, $entityType);
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

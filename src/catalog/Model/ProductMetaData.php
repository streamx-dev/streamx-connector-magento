<?php

namespace StreamX\ConnectorCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

class ProductMetaData
{
    private ?EntityMetadataInterface $productMetaData = null;
    private MetadataPool $metadataPool;

    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    public function get(): EntityMetadataInterface
    {
        if (null === $this->productMetaData) {
            $this->productMetaData = $this->metadataPool->getMetadata(
                ProductInterface::class
            );
        }

        return $this->productMetaData;
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Model;

use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class ProductMetaData
 */
class ProductMetaData
{
    /**
     * @var \Magento\Framework\EntityManager\EntityMetadataInterface
     */
    private $productMetaData;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * ProductMetaData constructor.
     *
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @return \Magento\Framework\EntityManager\EntityMetadataInterface
     */
    public function get()
    {
        if (null === $this->productMetaData) {
            $this->productMetaData = $this->metadataPool->getMetadata(
                \Magento\Catalog\Api\Data\ProductInterface::class
            );
        }

        return $this->productMetaData;
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Model;

use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class CategoryMetaData
 */
class CategoryMetaData
{

    /**
     * @var \Magento\Framework\EntityManager\EntityMetadataInterface
     */
    private $categoryMetaData;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * CategoryMetaData constructor.
     *
     * @param MetadataPool $metadataPool
     */
    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @return \Magento\Framework\EntityManager\EntityMetadataInterface
     * @throws \Exception
     */
    public function get()
    {
        if (null === $this->categoryMetaData) {
            $this->categoryMetaData = $this->metadataPool->getMetadata(
                \Magento\Catalog\Api\Data\CategoryInterface::class
            );
        }

        return $this->categoryMetaData;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model;

use Exception;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;

class CategoryMetaData
{
    private ?EntityMetadataInterface $categoryMetaData = null;
    private MetadataPool $metadataPool;

    public function __construct(MetadataPool $metadataPool)
    {
        $this->metadataPool = $metadataPool;
    }

    /**
     * @throws Exception
     */
    public function get(): EntityMetadataInterface
    {
        if (null === $this->categoryMetaData) {
            $this->categoryMetaData = $this->metadataPool->getMetadata(
                CategoryInterface::class
            );
        }

        return $this->categoryMetaData;
    }
}

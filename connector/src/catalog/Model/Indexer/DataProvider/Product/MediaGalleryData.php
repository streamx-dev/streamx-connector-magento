<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Api\LoadMediaGalleryInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;

class MediaGalleryData implements DataProviderInterface
{

    /**
     * @var CatalogConfigurationInterface
     */
    private $catalogConfig;

    /**
     * @var LoadMediaGalleryInterface
     */
    private $loadMediaGallery;

    /**
     * @var boolean
     */
    private $canIndexMediaGallery;

    public function __construct(
        CatalogConfigurationInterface $catalogConfig,
        LoadMediaGalleryInterface $galleryProcessor
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->loadMediaGallery = $galleryProcessor;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        if ($this->canIndexMediaGallery($storeId)) {
            return $this->loadMediaGallery->execute($indexData, $storeId);
        }

        return $indexData;
    }

    private function canIndexMediaGallery(int $storeId): bool
    {
        if (null === $this->canIndexMediaGallery) {
            $attributes = $this->catalogConfig->getAllowedAttributesToIndex($storeId);
            $this->canIndexMediaGallery = empty($attributes) || in_array('media_gallery', $attributes);
        }

        return $this->canIndexMediaGallery;
    }
}

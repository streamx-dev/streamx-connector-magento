<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\Product\LoadMediaGallery;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class MediaGalleryData implements DataProviderInterface
{
    private CatalogConfig $catalogConfig;
    private LoadMediaGallery $loadMediaGallery;
    private ?bool $canIndexMediaGallery = null;

    public function __construct(
        CatalogConfig $catalogConfig,
        LoadMediaGallery $loadMediaGallery
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->loadMediaGallery = $loadMediaGallery;
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

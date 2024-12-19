<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Api\LoadMediaGalleryInterface;

class MediaGalleryData implements DataProviderInterface {

    private LoadMediaGalleryInterface $loadMediaGallery;

    public function __construct(LoadMediaGalleryInterface $galleryProcessor) {
        $this->loadMediaGallery = $galleryProcessor;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array {
        return $this->loadMediaGallery->execute($indexData, $storeId);
    }
}

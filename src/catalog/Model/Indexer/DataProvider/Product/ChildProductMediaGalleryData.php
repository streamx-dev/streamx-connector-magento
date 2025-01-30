<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\Product\LoadMediaGallery;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ChildProductMediaGalleryData extends BaseMediaGalleryData {

    public function __construct(CatalogConfig $catalogConfig, LoadMediaGallery $loadMediaGallery) {
        parent::__construct($catalogConfig, $loadMediaGallery);
    }

    protected function getAttributesToIndex(int $storeId): array {
        return $this->catalogConfig->getChildAttributesToIndex($storeId);
    }
}

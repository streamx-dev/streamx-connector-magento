<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ChildProductAttributes;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

class ChildProductAttributeData extends BaseAttributeData
{
    public function __construct(
        LoggerInterface $logger,
        ChildProductAttributes $productAttributes,
        ProductAttributesProvider $resourceModel,
        ImageUrlManager $imageUrlManager,
        SlugGenerator $slugGenerator
    ) {
        parent::__construct($logger, $productAttributes, $resourceModel, $imageUrlManager, $slugGenerator);
    }
}

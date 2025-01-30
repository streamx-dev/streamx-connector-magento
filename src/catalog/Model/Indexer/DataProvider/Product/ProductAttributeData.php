<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Attribute\LoadAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

class ProductAttributeData extends BaseAttributeData
{
    public function __construct(
        LoggerInterface $logger,
        ProductAttributes $productAttributes,
        LoadAttributes $loadAttributes,
        ProductAttributesProvider $resourceModel,
        ImageUrlManager $imageUrlManager,
        SlugGenerator $slugGenerator
    ) {
        parent::__construct($logger, $productAttributes, $loadAttributes, $resourceModel, $imageUrlManager, $slugGenerator);
    }
}

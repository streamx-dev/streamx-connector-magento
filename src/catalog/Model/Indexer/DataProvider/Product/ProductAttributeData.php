<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributeDefinitions;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

class ProductAttributeData extends BaseAttributeData
{
    public function __construct(
        LoggerInterface $logger,
        ProductAttributes $productAttributes,
        LoadAttributeDefinitions $loadAttributeDefinitions,
        ProductAttributesProvider $resourceModel,
        ImageUrlManager $imageUrlManager,
        SlugGenerator $slugGenerator
    ) {
        parent::__construct($logger, $productAttributes, $loadAttributeDefinitions, $resourceModel, $imageUrlManager, $slugGenerator);
    }
}

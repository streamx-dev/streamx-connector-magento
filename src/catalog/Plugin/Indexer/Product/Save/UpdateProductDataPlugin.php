<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Product\Save;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use Magento\Catalog\Model\Product;

class UpdateProductDataPlugin
{
    private ProductProcessor $productProcessor;

    public function __construct(ProductProcessor $processor)
    {
        $this->productProcessor = $processor;
    }

    /**
     * Reindex data after product save/delete resource commit
     */
    public function afterReindex(Product $product): void
    {
        $this->productProcessor->reindexRow($product->getId());
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Plugin\Indexer\Product\Save;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\ProductProcessor;
use Magento\Catalog\Model\Product;

class UpdateProductDataPlugin
{
    /**
     * @var ProductProcessor
     */
    private $productProcessor;

    public function __construct(ProductProcessor $processor)
    {
        $this->productProcessor = $processor;
    }

    /**
     * Reindex data after product save/delete resource commit
     *
     * @return void
     */
    public function afterReindex(Product $product)
    {
        $this->productProcessor->reindexRow($product->getId());
    }
}

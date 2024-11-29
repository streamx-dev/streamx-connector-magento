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

    /**
     * UpdateProductData constructor.
     *
     * @param ProductProcessor $processor
     */
    public function __construct(ProductProcessor $processor)
    {
        $this->productProcessor = $processor;
    }

    /**
     * Reindex data after product save/delete resource commit
     *
     * @param Product $product
     *
     * @return void
     */
    public function afterReindex(Product $product)
    {
        $this->productProcessor->reindexRow($product->getId());
    }
}

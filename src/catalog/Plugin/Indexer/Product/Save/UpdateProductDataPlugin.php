<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Product\Save;

use Magento\Catalog\Model\Product;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;

class UpdateProductDataPlugin
{
    private ProductIndexer $productIndexer;

    public function __construct(ProductIndexer $indexer)
    {
        $this->productIndexer = $indexer;
    }

    /**
     * Reindex data after product save/delete resource commit
     */
    public function afterReindex(Product $product): void
    {
        $this->productIndexer->reindexRow($product->getId());
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer;

class ProductCategoryProcessor extends \Magento\Framework\Indexer\AbstractProcessor
{
    /**
     * Indexer ID
     */
    const INDEXER_ID = 'vsbridge_product_category';

    /**
     * Mark Vsbridge Product indexer as invalid
     */
    public function markIndexerAsInvalid(): void
    {
        $this->getIndexer()->invalidate();
    }
}

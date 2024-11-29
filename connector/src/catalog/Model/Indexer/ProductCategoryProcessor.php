<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

class ProductCategoryProcessor extends \Magento\Framework\Indexer\AbstractProcessor
{
    /**
     * Indexer ID
     */
    const INDEXER_ID = 'streamx_product_category';

    /**
     * Mark StreamX Product indexer as invalid
     */
    public function markIndexerAsInvalid(): void
    {
        $this->getIndexer()->invalidate();
    }
}

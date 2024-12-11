<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

class ProductCategoryProcessor extends \Magento\Framework\Indexer\AbstractProcessor
{
    /**
     * Indexer ID
     */
    public const INDEXER_ID = 'streamx_product_category_indexer';

    /**
     * Mark StreamX Product indexer as invalid
     */
    public function markIndexerAsInvalid(): void
    {
        $this->getIndexer()->invalidate();
    }
}

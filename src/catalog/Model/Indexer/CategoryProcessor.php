<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Magento\Framework\Indexer\AbstractProcessor;

class CategoryProcessor extends AbstractProcessor
{
    /**
     * @override field from base class
     */
    public const INDEXER_ID = 'streamx_category_indexer';
}

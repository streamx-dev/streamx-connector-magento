<?php

namespace StreamX\ConnectorTestTools\Impl;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Model\IndexerFactory;
use StreamX\ConnectorTestTools\Api\PriceIndexerInterface;

class PriceIndexerImpl implements PriceIndexerInterface {

    private IndexerInterface $magentoPriceIndexer;
    private IndexerInterface $streamxProductIndexer;

    public function __construct(IndexerFactory $indexerFactory) {
        $this->magentoPriceIndexer = $indexerFactory->create()->load('catalog_product_price');
        $this->streamxProductIndexer = $indexerFactory->create()->load('streamx_product_indexer');
    }

    public function reindexPrice(int $productId): void {
        $this->magentoPriceIndexer->reindexRow($productId);

        // When you run a Magento indexer from command line, Magento automatically triggers the reindexing of dependent indexers.
        // However, when you run the indexer via code, Magento doesn't automatically handle the dependent indexers like it does when you run the command via CLI.
        // Therefore, we must manually reindex the price for StreamX:
        $this->streamxProductIndexer->reindexRow($productId);
    }
}
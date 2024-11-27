<?php declare(strict_types=1);

namespace Streamx\Connector\Model\Indexer;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;

class ProductIndexer implements ActionInterface {

    public const INDEXER_ID = 'streamx_products';
    public const MVIEW_TABLE_NAME = 'streamx_products_cl';

    private LoggerInterface $logger;
    private ProductRepository $productRepository;
    private StreamxPublisher $streamxPublisher;
    private IndexerInterface $indexer;

    public function __construct(LoggerInterface $logger, ProductRepository $productRepository, StreamxPublisher $streamxPublisher, IndexerRegistry $indexerRegistry) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->streamxPublisher = $streamxPublisher;
        $this->indexer = $indexerRegistry->get(self::INDEXER_ID);
    }

    public function isUpdateOnSave(): bool {
        return !$this->isUpdateOnSchedule();
    }

    public function isUpdateOnSchedule(): bool {
        return $this->indexer->isScheduled();
    }

    // @Override
    public function executeRow($id) {
        $this->logInfo("executeRow($id)");
        $this->executeList([$id]);
    }

    // @Override
    public function executeList(array $ids) {
        $this->logInfo("executeList(" . json_encode($ids) . ")");
        
        $uniqueIds = array_unique($ids);
        foreach ($uniqueIds as $id) {
            $product = $this->productRepository->getById($id);
            // TODO: if there is no such product - it means it was deleted -> send unpublish message
            $this->streamxPublisher->publishToStreamX($product); // TODO: batch publish with multiple json ingestion message
        }
    }

    // @Override
    public function executeFull() {
        $this->logInfo("executeFull(): full reindexing is not implemented yet");

        // TODO: if the current index mode is Update On Schedule:
        // - select all rows from streamx_products_cl changelog table
        // - take distinct product ids from the result
        // - call executeList($ids)
        // - if success - delete the rows selected in first step
        // If the current index mode is Update On Save - return immediately since all changes were already processed (and there is no trigger to fill the _cl table)
    }

    private function logInfo(string $msg) {
        $date = date("Y-m-d H:i:s");
        $this->logger->info("$date ProductIndexer $msg");
    }
}
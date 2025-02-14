<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Cron\MView;

use Exception;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Mview\ViewInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

class StreamxIndexerMviewProcessor {

    private LoggerInterface $logger;
    private ViewInterface $viewInterface;
    private IndexerRegistry $indexerRegistry;

    public function __construct(LoggerInterface $logger, ViewInterface $viewInterface, IndexerRegistry $indexerRegistry) {
        $this->logger = $logger;
        $this->viewInterface = $viewInterface;
        $this->indexerRegistry = $indexerRegistry;
    }

    public function reindexProductMview(): void {
        $this->reindexMview(ProductProcessor::INDEXER_ID);
    }

    public function reindexCategoryMview(): void {
        $this->reindexMview(CategoryProcessor::INDEXER_ID);
    }

    public function reindexAttributeMview(): void {
        $this->reindexMview(AttributeProcessor::INDEXER_ID);
    }

    /**
     * Triggers processing new data from _cl tables subscribed by the given indexer's MView
     * @param string $indexerViewId view id (as in mview.xml file). Expecting that for each handled entity type, their view and indexer ids are the same
     * @throws Exception
     */
    public function reindexMview(string $indexerViewId): void {
        $indexer = $this->indexerRegistry->get($indexerViewId);
        if ($indexer->isScheduled()) {
            $this->logger->info("Start processing mview for $indexerViewId");
            $mView = $this->viewInterface->load($indexerViewId);
            $mView->update();
            $this->logger->info("Finished processing mview for $indexerViewId");
        }
    }

}

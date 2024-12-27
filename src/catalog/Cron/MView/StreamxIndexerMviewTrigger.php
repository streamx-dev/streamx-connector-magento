<?php
declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Cron\MView;

use Exception;
use Magento\Framework\Mview\ViewInterface;
use Psr\Log\LoggerInterface;

class StreamxIndexerMviewTrigger {

    private LoggerInterface $logger;
    private ViewInterface $viewInterface;
    private string $indexerViewId;

    public function __construct(
        LoggerInterface $logger,
        ViewInterface $viewInterface,
        string $indexerViewId
    ) {
        $this->logger = $logger;
        $this->viewInterface = $viewInterface;
        $this->indexerViewId = $indexerViewId;
    }

    public function getIndexerViewId(): string {
        return $this->indexerViewId;
    }

    /**
     * Triggers processing new data from _cl tables subscribed by the given indexer's MView
     * @return void
     * @throws Exception
     */
    public function reindexMview(): void {
        $viewId = $this->indexerViewId;
        try {
            $mView = $this->viewInterface->load($viewId);
            $mView->update();
            $this->logger->info("Incremental reindexing executed successfully for $viewId");
        } catch (Exception $e) {
            throw new Exception("Error during incremental reindexing $viewId: " . $e->getMessage(), -1, $e);
        }
    }

}

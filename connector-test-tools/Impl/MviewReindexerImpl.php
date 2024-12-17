<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Framework\Mview\ViewInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorTestTools\Api\MviewReindexerInterface;

class MviewReindexerImpl implements MviewReindexerInterface {

    private LoggerInterface $logger;
    private ViewInterface $viewInterface;

    public function __construct(LoggerInterface $logger, ViewInterface $viewInterface) {
        $this->logger = $logger;
        $this->viewInterface = $viewInterface;
    }

    /**
     * @throws Exception
     */
    public function reindexMview(string $indexerViewId): void {
        try {
            $mView = $this->viewInterface->load($indexerViewId);
            $mView->update();
            $this->logger->info("Incremental reindexing executed successfully for $indexerViewId");
        } catch (Exception $e) {
            throw new Exception("Error during incremental reindexing $indexerViewId: " . $e->getMessage(), -1, $e);
        }
    }
}
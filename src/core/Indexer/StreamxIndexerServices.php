<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\StreamxAvailabilityCheckerFactory;
use StreamX\ConnectorCore\Client\StreamxClientFactory;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\System\GeneralConfig;

class StreamxIndexerServices {

    private GeneralConfig $connectorConfig;
    private IndexedStoresProvider $indexedStoresProvider;
    private LoggerInterface $logger;
    private OptimizationSettings $optimizationSettings;
    private StreamxClientFactory $streamxClientFactory;
    private StreamxAvailabilityCheckerFactory $streamxAvailabilityCheckerFactory;
    private IndexerRegistry $indexerRegistry;
    private IndexersConfigInterface $indexersConfig;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexedStoresProvider $indexedStoresProvider,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientFactory $streamxClientFactory,
        StreamxAvailabilityCheckerFactory $streamxAvailabilityCheckerFactory,
        IndexerRegistry $indexerRegistry,
        IndexersConfigInterface $indexersConfig
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->indexedStoresProvider = $indexedStoresProvider;
        $this->logger = $logger;
        $this->optimizationSettings = $optimizationSettings;
        $this->streamxClientFactory = $streamxClientFactory;
        $this->streamxAvailabilityCheckerFactory = $streamxAvailabilityCheckerFactory;
        $this->indexerRegistry = $indexerRegistry;
        $this->indexersConfig = $indexersConfig;
    }

    public function getConnectorConfig(): GeneralConfig {
        return $this->connectorConfig;
    }

    public function getIndexedStoresProvider(): IndexedStoresProvider {
        return $this->indexedStoresProvider;
    }

    public function getLogger(): LoggerInterface {
        return $this->logger;
    }

    public function getOptimizationSettings(): OptimizationSettings {
        return $this->optimizationSettings;
    }

    public function getStreamxClientFactory(): StreamxClientFactory {
        return $this->streamxClientFactory;
    }

    public function getStreamxAvailabilityCheckerFactory(): StreamxAvailabilityCheckerFactory {
        return $this->streamxAvailabilityCheckerFactory;
    }

    public function getIndexerRegistry(): IndexerRegistry {
        return $this->indexerRegistry;
    }

    public function getIndexersConfig(): IndexersConfigInterface {
        return $this->indexersConfig;
    }
}

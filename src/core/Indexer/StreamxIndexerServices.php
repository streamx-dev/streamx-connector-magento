<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConfiguration;
use StreamX\ConnectorCore\Client\StreamxAvailabilityChecker;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\System\GeneralConfig;

class StreamxIndexerServices {

    private GeneralConfig $connectorConfig;
    private IndexedStoresProvider $indexedStoresProvider;
    private LoggerInterface $logger;
    private OptimizationSettings $optimizationSettings;
    private StreamxClient $streamxClient;
    private StreamxAvailabilityChecker $streamxAvailabilityChecker;
    private RabbitMqConfiguration $rabbitMqConfiguration;
    private IndexerRegistry $indexerRegistry;
    private IndexersConfigInterface $indexersConfig;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexedStoresProvider $indexedStoresProvider,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClient $streamxClient,
        StreamxAvailabilityChecker $streamxAvailabilityChecker,
        RabbitMqConfiguration $rabbitMqConfiguration,
        IndexerRegistry $indexerRegistry,
        IndexersConfigInterface $indexersConfig
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->indexedStoresProvider = $indexedStoresProvider;
        $this->logger = $logger;
        $this->optimizationSettings = $optimizationSettings;
        $this->streamxClient = $streamxClient;
        $this->streamxAvailabilityChecker = $streamxAvailabilityChecker;
        $this->rabbitMqConfiguration = $rabbitMqConfiguration;
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

    public function getStreamxClient(): StreamxClient {
        return $this->streamxClient;
    }

    public function getStreamxAvailabilityChecker(): StreamxAvailabilityChecker {
        return $this->streamxAvailabilityChecker;
    }

    public function getRabbitMqConfiguration(): RabbitMqConfiguration {
        return $this->rabbitMqConfiguration;
    }

    public function getIndexerRegistry(): IndexerRegistry {
        return $this->indexerRegistry;
    }

    public function getIndexersConfig(): IndexersConfigInterface {
        return $this->indexersConfig;
    }
}

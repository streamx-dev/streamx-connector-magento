<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConfiguration;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\System\GeneralConfig;

class StreamxIndexerServices {

    private GeneralConfig $generalConfig;
    private IndexedStoresProvider $indexedStoresProvider;
    private LoggerInterface $logger;
    private StreamxClient $streamxClient;
    private RabbitMqConfiguration $rabbitMqConfiguration;
    private IndexerRegistry $indexerRegistry;
    private IndexersConfigInterface $indexersConfig;

    public function __construct(
        GeneralConfig $generalConfig,
        IndexedStoresProvider $indexedStoresProvider,
        LoggerInterface $logger,
        StreamxClient $streamxClient,
        RabbitMqConfiguration $rabbitMqConfiguration,
        IndexerRegistry $indexerRegistry,
        IndexersConfigInterface $indexersConfig
    ) {
        $this->generalConfig = $generalConfig;
        $this->indexedStoresProvider = $indexedStoresProvider;
        $this->logger = $logger;
        $this->streamxClient = $streamxClient;
        $this->rabbitMqConfiguration = $rabbitMqConfiguration;
        $this->indexerRegistry = $indexerRegistry;
        $this->indexersConfig = $indexersConfig;
    }

    public function getGeneralConfig(): GeneralConfig {
        return $this->generalConfig;
    }

    public function getIndexedStoresProvider(): IndexedStoresProvider {
        return $this->indexedStoresProvider;
    }

    public function getLogger(): LoggerInterface {
        return $this->logger;
    }

    public function getStreamxClient(): StreamxClient {
        return $this->streamxClient;
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

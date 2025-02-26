<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\Indexer\SaveHandler\Batch;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\BasicDataLoader;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Index\IndexerDefinition;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;
use StreamX\ConnectorCore\System\GeneralConfig;
use Traversable;

abstract class BaseStreamxIndexer implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GeneralConfig $connectorConfig;
    private IndexableStoresProvider $indexableStoresProvider;
    private BasicDataLoader $entityDataLoader;
    private LoggerInterface $logger;
    private OptimizationSettings $optimizationSettings;
    private StreamxClientConfiguration $clientConfiguration;
    private IndexerDefinition $indexerDefinition;
    private string $indexerName;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexableStoresProvider $indexableStoresProvider,
        BasicDataLoader $entityDataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientConfiguration $clientConfiguration,
        IndexerDefinition $indexerDefinition
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->indexableStoresProvider = $indexableStoresProvider;
        $this->entityDataLoader = $entityDataLoader;
        $this->logger = $logger;
        $this->optimizationSettings = $optimizationSettings;
        $this->clientConfiguration = $clientConfiguration;
        $this->indexerDefinition = $indexerDefinition;
        $this->indexerName = $indexerDefinition->getName();
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id)
    {
        $this->loadDocumentsAndSaveIndex([$id]);
    }

    /**
     * @inheritdoc
     */
    public function execute($ids)
    {
        $this->loadDocumentsAndSaveIndex($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids)
    {
        $this->loadDocumentsAndSaveIndex($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeFull()
    {
        $this->loadDocumentsAndSaveIndex([]);
    }

    private function loadDocumentsAndSaveIndex(array $ids): void {
        if (!$this->connectorConfig->isEnabled()) {
            $this->logger->info("StreamX Connector is disabled, skipping indexing $this->indexerName");
            return;
        }

        foreach ($this->indexableStoresProvider->getStores() as $store) {
            $storeId = (int) $store->getId();

            $client = new StreamxClient($this->logger, $this->clientConfiguration, $store);
            if ($this->optimizationSettings->shouldPerformStreamxAvailabilityCheck() && !$client->isStreamxAvailable()) {
                $this->logger->info("Cannot reindex $this->indexerName for store $storeId - StreamX is not available");
                continue;
            }

            $this->logger->info("Start indexing $this->indexerName for store $storeId");
            $documents = $this->entityDataLoader->loadData($storeId, $ids);
            $this->saveIndex($documents, $storeId, $client);
            $this->logger->info("Finished indexing $this->indexerName for store $storeId");
        }
    }

    public final function saveIndex(Traversable $documents, int $storeId, StreamxClient $client): void {
        $batchSize = $this->optimizationSettings->getBatchIndexingSize();

        foreach ((new Batch())->getItems($documents, $batchSize) as $docs) {
            $this->processEntitiesBatch($docs, $storeId, $client);
        }
    }

    protected function processEntitiesBatch(array $entities, int $storeId, StreamxClient $client): void {
        $entitiesToPublish = [];
        $idsToUnpublish = [];
        foreach ($entities as $id => $entity) {
            if (empty($entity)) {
                $idsToUnpublish[] = $id;
            } else {
                $entitiesToPublish[$id] = $entity;
            }
        }

        if (!empty($entitiesToPublish)) {
            $this->addData($entitiesToPublish, $storeId);
            $client->publish(array_values($entitiesToPublish), $this->indexerDefinition->getName());
        }

        if (!empty($idsToUnpublish)) {
            $client->unpublish($idsToUnpublish, $this->indexerDefinition->getName());
        }
    }

    private function addData(array &$entities, int $storeId): void {
        foreach ($this->indexerDefinition->getDataProviders() as $dataProvider) {
            $dataProvider->addData($entities, $storeId);
        }
    }
}

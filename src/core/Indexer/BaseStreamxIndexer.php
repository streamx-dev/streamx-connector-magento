<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Exception;
use Magento\Framework\Indexer\AbstractProcessor;
use Magento\Framework\Indexer\ActionInterface as IndexerAction;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Mview\ActionInterface as MViewAction;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\BasicDataLoader;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Client\StreamxAvailabilityChecker;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\System\GeneralConfig;
use StreamX\ConnectorCore\Traits\ExceptionLogger;
use Traversable;

abstract class BaseStreamxIndexer extends AbstractProcessor implements IndexerAction, MViewAction {

    use ExceptionLogger;

    private GeneralConfig $connectorConfig;
    private IndexedStoresProvider $indexedStoresProvider;
    private BasicDataLoader $entityDataLoader;
    protected LoggerInterface $logger;
    private OptimizationSettings $optimizationSettings;
    private StreamxClient $streamxClient;
    private StreamxAvailabilityChecker $streamxAvailabilityChecker;
    /**
     * @var DataProviderInterface[]
     */
    private array $dataProviders;
    private string $indexerId;

    public function __construct(
        StreamxIndexerServices $indexerServices,
        BasicDataLoader $entityDataLoader
    ) {
        parent::__construct($indexerServices->getIndexerRegistry());
        $this->connectorConfig = $indexerServices->getConnectorConfig();
        $this->indexedStoresProvider = $indexerServices->getIndexedStoresProvider();
        $this->entityDataLoader = $entityDataLoader;
        $this->logger = $indexerServices->getLogger();
        $this->optimizationSettings = $indexerServices->getOptimizationSettings();
        $this->streamxClient = $indexerServices->getStreamxClient();
        $this->streamxAvailabilityChecker = $indexerServices->getStreamxAvailabilityChecker();
        $indexerDefinition = $indexerServices->getIndexersConfig()->getById(static::INDEXER_ID);
        $this->dataProviders = $indexerDefinition->getDataProviders();
        $this->indexerId = $indexerDefinition->getIndexerId();
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id) {
        $this->loadAndIngestEntities([$id]);
    }

    /**
     * @inheritdoc
     */
    public function execute($ids) {
        $this->loadAndIngestEntities($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids) {
        $this->loadAndIngestEntities($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeFull() {
        $this->loadAndIngestEntities([]);
    }

    private function loadAndIngestEntities(array $ids): void {
        if (!$this->connectorConfig->isEnabled()) {
            $this->logger->info("StreamX Connector is disabled, skipping indexing $this->indexerId");
            return;
        }

        $shouldPerformStreamxAvailabilityCheck = $this->optimizationSettings->shouldPerformStreamxAvailabilityCheck();

        foreach ($this->indexedStoresProvider->getStores() as $store) {
            $storeId = (int) $store->getId();

            if ($shouldPerformStreamxAvailabilityCheck && !$this->streamxAvailabilityChecker->isStreamxAvailable($storeId)) {
                $this->logger->info("Cannot reindex $this->indexerId for store $storeId - StreamX is not available");
                continue;
            }

            $this->logger->info("Start indexing $this->indexerId for store $storeId");
            $entities = $this->entityDataLoader->loadData($storeId, $ids);
            $this->ingestEntities($entities, $store);
            $this->logger->info("Finished indexing $this->indexerId for store $storeId");
        }
    }

    protected function ingestEntities(Traversable $entities, StoreInterface $store): void {
        $batchSize = $this->optimizationSettings->getBatchIndexingSize();

        foreach ((new Batch())->getItems($entities, $batchSize) as $entitiesBatch) {
            $this->processEntitiesBatch($entitiesBatch, $store);
        }
    }

    private function processEntitiesBatch(array $entities, StoreInterface $store): void {
        $entitiesToPublish = [];
        $idsToUnpublish = [];
        foreach ($entities as $id => $entity) {
            if ($entity) {
                $entitiesToPublish[$id] = $entity;
            } else {
                $idsToUnpublish[] = $id;
            }
        }

        if (!empty($entitiesToPublish)) {
            $this->addDataAndPublish($entitiesToPublish, $store);
        }

        if (!empty($idsToUnpublish)) {
            $this->unpublish($idsToUnpublish, $store);
        }
    }

    private function addDataAndPublish(array $entities, StoreInterface $store): void {
        $storeId = (int) $store->getId();
        try {
            foreach ($this->dataProviders as $dataProvider) {
                $dataProvider->addData($entities, $storeId);
            }
        } catch (Exception $e) {
            $entityIds = array_column($entities, 'id');
            $this->logExceptionAsError(
                'Error while adding data to entities, cannot publish. Keys of entities in batch: ' . implode(', ', $entityIds),
                $e
            );
            return;
        }

        $this->streamxClient->publish(array_values($entities), $this->indexerId, $store);
    }

    private function unpublish(array $idsToUnpublish, StoreInterface $store): void {
        $this->streamxClient->unpublish($idsToUnpublish, $this->indexerId, $store);
    }
}

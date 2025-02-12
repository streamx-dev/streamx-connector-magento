<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCatalog\Model\Indexer\Action\BaseAction;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\System\GeneralConfig;

abstract class BaseStreamxIndexer implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GeneralConfig $connectorConfig;
    private IndexableStoresProvider $indexableStoresProvider;
    private GenericIndexerHandler $indexHandler;
    private BaseAction $action;
    private LoggerInterface $logger;
    private OptimizationSettings $optimizationSettings;
    private ClientResolver $clientResolver;
    private string $entityTypeName;

    public function __construct(
        GeneralConfig $connectorConfig,
        GenericIndexerHandler $indexerHandler,
        IndexableStoresProvider $indexableStoresProvider,
        BaseAction $action,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        ClientResolver $clientResolver,
        string $entityTypeName
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->indexHandler = $indexerHandler;
        $this->indexableStoresProvider = $indexableStoresProvider;
        $this->action = $action;
        $this->logger = $logger;
        $this->optimizationSettings = $optimizationSettings;
        $this->clientResolver = $clientResolver;
        $this->entityTypeName = $entityTypeName;
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

    /**
     * @throws StreamxClientException
     */
    private function loadDocumentsAndSaveIndex(array $ids): void {
        if (!$this->connectorConfig->isEnabled()) {
            $this->logger->info("StreamX Connector is disabled, skipping indexing $this->entityTypeName");
            return;
        }

        foreach ($this->indexableStoresProvider->getStores() as $store) {
            $storeId = (int) $store->getId();

            $client = $this->clientResolver->getClient($storeId);
            if ($this->optimizationSettings->shouldPerformStreamxAvailabilityCheck() && !$client->isStreamxAvailable()) {
                $this->logger->info("Cannot reindex $this->entityTypeName data for store $storeId - StreamX is not available");
                continue;
            }

            $this->logger->info("Start indexing $this->entityTypeName from store $storeId");
            $documents = $this->action->loadData($storeId, $ids);
            $this->indexHandler->saveIndex($documents, $storeId, $client);
            $this->logger->info("Finished indexing $this->entityTypeName from store $storeId");
        }
    }
}

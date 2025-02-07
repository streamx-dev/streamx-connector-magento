<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCatalog\Model\Indexer\Action\BaseAction;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;
use StreamX\ConnectorCore\System\GeneralConfigInterface;

abstract class BaseStreamxIndexer implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GeneralConfigInterface $connectorConfig;
    private StoreManager $storeManager;
    private GenericIndexerHandler $indexHandler;
    private BaseAction $action;
    private LoggerInterface $logger;
    private string $entityTypeName;

    public function __construct(
        GeneralConfigInterface $connectorConfig,
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        BaseAction $action,
        LoggerInterface $logger,
        string $entityType
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->indexHandler = $indexerHandler;
        $this->storeManager = $storeManager;
        $this->action = $action;
        $this->logger = $logger;
        $this->entityTypeName = $entityType;
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
        $this->loadDocumentsAndSaveIndex();
    }

    /**
     * @throws NoSuchEntityException
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    private function loadDocumentsAndSaveIndex($ids = []): void {
        if (!$this->connectorConfig->isEnabled()) {
            $this->logger->info("StreamX Connector is disabled, skipping indexing $this->entityTypeName");
            return;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $this->logger->info("Indexing $this->entityTypeName from store $storeId");
            $documents = $this->action->loadData($storeId, $ids);
            $this->indexHandler->saveIndex($documents, $storeId);
            $this->logger->info("Indexed $this->entityTypeName from store $storeId");
        }
    }
}

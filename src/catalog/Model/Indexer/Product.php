<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Product as ProductAction;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GenericIndexerHandler $indexHandler;
    private ProductAction $productAction;
    private StoreManager $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        ProductAction $action,
        LoggerInterface $logger
    ) {
        $this->indexHandler = $indexerHandler;
        $this->productAction = $action;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
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
    public function executeFull()
    {
        $this->loadDocumentsAndSaveIndex();
    }

    private function loadDocumentsAndSaveIndex($ids = []): void {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->logger->info("Indexing Products from store $storeId");

            $documents = $this->productAction->rebuild($storeId, $ids);
            $this->indexHandler->saveIndex($documents, $store);
            $this->logger->info("Indexed Products from store $storeId");
        }
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}

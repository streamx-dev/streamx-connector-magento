<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Attribute as AttributeAction;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;

class Attribute implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GenericIndexerHandler $indexHandler;
    private AttributeAction $attributeAction;
    private StoreManager $storeManager;
    private LoggerInterface $logger;

    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        AttributeAction $action,
        LoggerInterface $logger
    ) {
        $this->indexHandler = $indexerHandler;
        $this->attributeAction = $action;
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
            $this->logger->info("Indexing Attributes from store $storeId");

            $documents = $this->attributeAction->rebuild($ids);
            $this->indexHandler->saveIndex($documents, $store);
            $this->logger->info("Indexed Attributes from store $storeId");
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

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\Action\Category as Action;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;

class Category implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GenericIndexerHandler $indexHandler;
    private Action $categoryAction;
    private StoreManager $storeManager;

    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        Action $action
    ) {
        $this->categoryAction = $action;
        $this->storeManager = $storeManager;
        $this->indexHandler = $indexerHandler;
    }

    /**
     * @inheritdoc
     */
    public function execute($ids)
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->indexHandler->saveIndex($this->categoryAction->rebuild($storeId, $ids), $store);
            $this->indexHandler->cleanUpByTransactionKey($store, $ids);
        }
    }

    /**
     * @inheritdoc
     */
    public function executeFull()
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->indexHandler->saveIndex($this->categoryAction->rebuild($store->getId()), $store);
            $this->indexHandler->cleanUpByTransactionKey($store);
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

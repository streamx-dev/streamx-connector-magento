<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\Action\Product as ProductAction;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GenericIndexerHandler $indexHandler;
    private ProductAction $productAction;
    private StoreManager $storeManager;

    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        ProductAction $action
    ) {
        $this->indexHandler = $indexerHandler;
        $this->productAction = $action;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function execute($ids)
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $this->indexHandler->saveIndex($this->productAction->rebuild($storeId, $ids), $store);
            $this->indexHandler->cleanUpByIds($store, $ids);
        }
    }

    /**
     * @inheritdoc
     */
    public function executeFull()
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->indexHandler->saveIndex($this->productAction->rebuild($store->getId()), $store);
            $this->indexHandler->cleanUpByIds($store);
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

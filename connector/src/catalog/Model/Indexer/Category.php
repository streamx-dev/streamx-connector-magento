<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\Action\Category as Action;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;
use StreamX\ConnectorCore\Cache\Processor as CacheProcessor;

class Category implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var GenericIndexerHandler
     */
    private $indexHandler;

    /**
     * @var Action
     */
    private $categoryAction;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CacheProcessor
     */
    private $cacheProcessor;

    public function __construct(
        CacheProcessor $cacheProcessor,
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        Action $action
    ) {
        $this->categoryAction = $action;
        $this->storeManager = $storeManager;
        $this->indexHandler = $indexerHandler;
        $this->cacheProcessor = $cacheProcessor;
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
            $this->cacheProcessor->cleanCacheByDocIds($storeId, $this->indexHandler->getTypeName(), $ids);
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
            $this->cacheProcessor->cleanCacheByTags($store->getId(), [$this->indexHandler->getTypeName()]);
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

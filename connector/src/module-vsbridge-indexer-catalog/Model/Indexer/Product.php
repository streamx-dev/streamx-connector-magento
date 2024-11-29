<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\Action\Product as ProductAction;

use Divante\VsbridgeIndexerCore\Indexer\StoreManager;
use Divante\VsbridgeIndexerCore\Indexer\GenericIndexerHandler;
use Divante\VsbridgeIndexerCore\Cache\Processor as CacheProcessor;

class Product implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var GenericIndexerHandler
     */
    private $indexHandler;

    /**
     * @var ProductAction
     */
    private $productAction;

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
        ProductAction $action
    ) {
        $this->productAction = $action;
        $this->indexHandler = $indexerHandler;
        $this->storeManager = $storeManager;
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
            $this->indexHandler->saveIndex($this->productAction->rebuild($storeId, $ids), $store);
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
            $this->indexHandler->saveIndex($this->productAction->rebuild($store->getId()), $store);
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

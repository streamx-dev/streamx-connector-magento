<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\Action\Product as ProductAction;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;

class ProductCategory implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GenericIndexerHandler $indexHandler;
    private ProductAction $productAction;
    private StoreManager $storeManager;

    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        ProductAction $action
    ) {
        $this->productAction = $action;
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
            $this->rebuild($store, $ids);
        }
    }

    /**
     * @inheritdoc
     */
    public function executeFull()
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->rebuild($store);
        }
    }

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function rebuild($store, array $productIds = [])
    {
        $this->indexHandler->updateIndex(
            $this->productAction->rebuild($store->getId(), $productIds),
            $store,
            ['category_data']
        );
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

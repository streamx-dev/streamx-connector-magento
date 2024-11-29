<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\Action\Attribute as AttributeAction;
use Divante\VsbridgeIndexerCore\Indexer\GenericIndexerHandler;
use Divante\VsbridgeIndexerCore\Indexer\StoreManager;

class Attribute implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    /**
     * @var GenericIndexerHandler
     */
    private $indexHandler;

    /**
     * @var AttributeAction
     */
    private $attributeAction;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * Attribute constructor.
     *
     * @param GenericIndexerHandler $indexerHandler
     * @param StoreManager $storeManager
     * @param AttributeAction $action
     */
    public function __construct(
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        AttributeAction $action
    ) {
        $this->indexHandler = $indexerHandler;
        $this->attributeAction = $action;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int[] $ids
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($ids)
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->indexHandler->saveIndex($this->attributeAction->rebuild($ids), $store);
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
            $this->indexHandler->saveIndex($this->attributeAction->rebuild(), $store);
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

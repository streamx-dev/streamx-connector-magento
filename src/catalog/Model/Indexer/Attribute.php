<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Attribute as AttributeAction;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;

class Attribute implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    private GenericIndexerHandler $indexHandler;
    private AttributeAction $attributeAction;
    private StoreManager $storeManager;

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
     * @throws NoSuchEntityException
     */
    public function execute($ids)
    {
        $stores = $this->storeManager->getStores();

        foreach ($stores as $store) {
            $this->indexHandler->saveIndex($this->attributeAction->rebuild($ids), $store);
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

<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins;

use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Indexer\IndexerInterface;
use Psr\Log\LoggerInterface;

class ProductObserver {

    // private IndexerInterface $indexer;
    private ProductAction $productAction;
    private LoggerInterface $logger;

    public function __construct(IndexerRegistry $indexerRegistry, ProductAction $productAction, LoggerInterface $logger) {
        // $this->indexer = $indexerRegistry->get('streamx_products_indexer');
        $this->productAction = $productAction;
        $this->logger = $logger;
    }

    public function afterSave(ProductResource $productResource, ProductResource $result, ProductModel $product) {
        $this->logger->info("ProductObserver afterSave(" . $product->getId() . ")");
        $productResource->addCommitCallback(function () use ($product) {
            // Note: older versions of Magento communicated with the code of class attached to indexer using indexer class. This is not deprecated. The new approach is to call methods of the action class directly
            // if (!$this->indexer->isScheduled()) {
            //    $this->indexer->reindexRow($product->getId());
            // }
            $this->productAction->executeRow($product->getId());
        });

        return $result;
    }

    public function afterDelete(ProductResource $productResource, ProductResource $result, ProductModel $product) {
        $this->logger->info("ProductObserver afterDelete(" . $product->getId() . ")");
        $productResource->addCommitCallback(function () use ($product) {
            //if (!$this->indexer->isScheduled()) {
            //    $this->indexer->reindexRow($product->getId());
            //}
            $this->productAction->executeRow($product->getId());
        });

        return $result;
    }

    public function afterUpdateAttributes(Action $subject, Action $result = null, $productIds) {
        $this->logger->info("ProductObserver afterUpdateAttributes(" . json_encode($productIds) . ")");
        //if (!$this->indexer->isScheduled()) {
        //    $this->indexer->reindexList(array_unique($productIds));
        //}
        $this->productAction->executeList(array_unique($productIds));

        return $result;
    }

    public function afterUpdateWebsites(Action $subject, Action $result = null, array $productIds) {
        $this->logger->info("afterUpdateWebsites($productIds)");
        //if (!$this->indexer->isScheduled()) {
        //    $this->indexer->reindexList(array_unique($productIds));
        //}
        $this->productAction->executeList(array_unique($productIds));

        return $result;
    }
}
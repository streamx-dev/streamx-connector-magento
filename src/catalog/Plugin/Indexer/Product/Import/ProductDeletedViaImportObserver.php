<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Product\Import;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;

/**
 * The feature of importing products by Admin can also be used to delete existing products.
 * This observer intercepts IDs of products to delete and triggers deleting them from StreamX.
 */
class ProductDeletedViaImportObserver implements ObserverInterface {

    private LoggerInterface $logger;
    private ProductIndexer $productIndexer;

    public function __construct(LoggerInterface $logger, ProductIndexer $productIndexer) {
        $this->logger = $logger;
        $this->productIndexer = $productIndexer;
    }

    public function execute(Observer $observer) {
        if ($this->productIndexer->isIndexerScheduled()) {
            // do nothing if the indexer is currently in Update By Schedule mode - mView should collect the product IDs into streamx_product_indexer_cl table
            return;
        }
        $productIds = array_map('intval', $observer->getEvent()->getData('ids_to_delete'));
        if (!empty($productIds)) {
            $this->logger->info('Reindexing products deleted via import; IDs: ' . json_encode($productIds));
            $this->productIndexer->reindexList($productIds);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;

class SalesOrderShipmentObserver implements ObserverInterface
{
    private LoggerInterface $logger;
    private ProductIndexer $productIndexer;

    public function __construct(LoggerInterface $logger, ProductIndexer $indexer) {
        $this->logger = $logger;
        $this->productIndexer = $indexer;
    }

    public function execute(Observer $observer) {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        $order = $shipment->getOrder();

        $productIds = [];
        foreach ($order->getItems() as $item) {
            $productIds[] = $item->getProductId();
        }

        $this->logger->info('Reindexing shipped products: ' . json_encode($productIds));
        $this->productIndexer->reindexList($productIds);
    }
}
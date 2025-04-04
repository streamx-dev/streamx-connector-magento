<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

class SalesOrderShipmentObserver implements ObserverInterface
{
    private LoggerInterface $logger;
    private ProductProcessor $productProcessor;

    public function __construct(LoggerInterface $logger, ProductProcessor $processor) {
        $this->logger = $logger;
        $this->productProcessor = $processor;
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
        $this->productProcessor->reindexList($productIds);
    }
}
<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use StreamX\ConnectorTestEndpoints\Api\ObserverRunnerInterface;

class ObserverRunnerImpl implements ObserverRunnerInterface {

    public function execute(string $observerClassName): void {
        $objectManager = ObjectManager::getInstance();

        /** @var $observer ObserverInterface */
        $observer = $objectManager->get($observerClassName);
        $observer->execute(new Observer());
    }
}
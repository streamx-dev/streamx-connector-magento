<?php

namespace StreamX\ConnectorCore\Indexer;

class DataProviderProcessorFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @return mixed
     */
    public function get(string $instanceName)
    {
        return $this->objectManager->get($instanceName);
    }
}

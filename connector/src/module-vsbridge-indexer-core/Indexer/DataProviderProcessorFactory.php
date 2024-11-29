<?php

namespace Divante\VsbridgeIndexerCore\Indexer;

class DataProviderProcessorFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $instanceName
     *
     * @return mixed
     */
    public function get($instanceName)
    {
        return $this->objectManager->get($instanceName);
    }
}

<?php

namespace Divante\VsbridgeIndexerCore\Indexer;

class MappingProcessorFactory
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
     * @param string $instanceName
     *
     * @return mixed
     */
    public function get($instanceName)
    {
        return $this->objectManager->get($instanceName);
    }
}

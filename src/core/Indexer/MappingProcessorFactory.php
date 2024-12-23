<?php

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\ObjectManagerInterface;

class MappingProcessorFactory
{
    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager) {
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

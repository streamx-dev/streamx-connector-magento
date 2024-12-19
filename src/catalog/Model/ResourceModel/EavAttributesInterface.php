<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

interface EavAttributesInterface
{

    /**
     * @throws \Exception
     */
    public function loadAttributesData(int $storeId, array $entityIds): array;

    public function canIndexAttribute(\Magento\Eav\Model\Entity\Attribute $attribute): bool;
}

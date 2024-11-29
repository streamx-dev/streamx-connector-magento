<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel;

interface EavAttributesInterface
{

    /**
     * @throws \Exception
     */
    public function loadAttributesData(int $storeId, array $entityIds, array $requiredAttributes = null): array;

    public function canIndexAttribute(\Magento\Eav\Model\Entity\Attribute $attribute, array $allowedAttributes = null): bool;
}

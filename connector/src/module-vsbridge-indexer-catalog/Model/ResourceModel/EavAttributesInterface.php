<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel;

interface EavAttributesInterface
{

    /**
     * @param int $storeId
     * @param array $entityIds
     * @param array $requiredAttributes
     *
     * @return array
     * @throws \Exception
     */
    public function loadAttributesData($storeId, array $entityIds, array $requiredAttributes = null);

    /**
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param array|null $allowedAttributes
     *
     * @return bool
     */
    public function canIndexAttribute(\Magento\Eav\Model\Entity\Attribute $attribute, array $allowedAttributes = null);
}

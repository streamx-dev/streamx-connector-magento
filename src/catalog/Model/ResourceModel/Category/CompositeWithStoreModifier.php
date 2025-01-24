<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

class CompositeWithStoreModifier extends CompositeBaseSelectModifier
{
    public function __construct(StoreSelectModifier $storeSelectModifier)
    {
        parent::__construct($storeSelectModifier);
    }
}
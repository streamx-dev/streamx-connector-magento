<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

class CompositeWithStoreModifier extends CompositeBaseSelectModifier
{
    public function __construct(StoreSelectModifier $storeSelectModifier)
    {
        parent::__construct($storeSelectModifier);
    }
}
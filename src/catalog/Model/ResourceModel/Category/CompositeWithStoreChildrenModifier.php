<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

class CompositeWithStoreChildrenModifier extends CompositeBaseSelectModifier
{
    public function __construct(
        StoreSelectModifier $storeSelectModifier,
        ActiveSelectModifier $activeSelectModifier
    ) {
        parent::__construct($storeSelectModifier, $activeSelectModifier);
    }
}
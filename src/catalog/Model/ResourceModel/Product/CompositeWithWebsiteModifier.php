<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;

class CompositeWithWebsiteModifier implements SelectModifierInterface
{
    /**
     * @var SelectModifierInterface[]
     */
    private array $selectModifiers;

    public function __construct(
        WebsiteSelectModifier $websiteSelectModifier,
        StatusSelectModifier $activeSelectModifier
    ) {
        $this->selectModifiers = [$websiteSelectModifier, $activeSelectModifier];
    }

    /**
     * {@inheritdoc}
     */
    public function modify(Select $select, int $storeId): void
    {
        foreach ($this->selectModifiers as $selectModifiers) {
            $selectModifiers->modify($select, $storeId);
        }
    }
}
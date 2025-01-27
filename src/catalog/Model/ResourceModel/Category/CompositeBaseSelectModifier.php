<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;

abstract class CompositeBaseSelectModifier
{
    /**
     * @var SelectModifierInterface[]
     */
    private array $selectModifiers;

    public function __construct(SelectModifierInterface... $selectModifier)
    {
        $this->selectModifiers = $selectModifier;
    }

    /**
     * Modify the select statement
     */
    public function modify(Select $select, int $storeId): void
    {
        foreach ($this->selectModifiers as $selectModifier) {
            $selectModifier->modify($select, $storeId);
        }
    }
}

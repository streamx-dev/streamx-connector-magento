<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Magento\Framework\DB\Select;

class CompositeSelectModifier
{
    /**
     * @var SelectModifierInterface[]
     */
    private array $selectModifiers;

    public function __construct(SelectModifierInterface... $selectModifier)
    {
        $this->selectModifiers = $selectModifier;
    }

    public function modifyAll(Select $select, int $storeId): void
    {
        foreach ($this->selectModifiers as $selectModifier) {
            $selectModifier->modify($select, $storeId);
        }
    }
}

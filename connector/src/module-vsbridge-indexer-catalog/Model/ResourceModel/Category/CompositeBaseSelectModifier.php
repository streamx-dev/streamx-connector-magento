<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\InputException;

class CompositeBaseSelectModifier implements BaseSelectModifierInterface
{
    /**
     * @var BaseSelectModifierInterface[]
     */
    private $baseSelectModifiers;

    /**
     * @param BaseSelectModifierInterface[] $baseSelectModifiers
     * @throws InputException
     */
    public function __construct(array $baseSelectModifiers)
    {
        foreach ($baseSelectModifiers as $baseSelectModifier) {
            if (!$baseSelectModifier instanceof BaseSelectModifierInterface) {
                throw new InputException(
                    __(
                        'Modifier %1 doesn\'t implement BaseSelectModifierInterface',
                        get_class($baseSelectModifier)
                    )
                );
            }
        }

        $this->baseSelectModifiers = $baseSelectModifiers;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Select $select, int $storeId): Select
    {
        foreach ($this->baseSelectModifiers as $baseSelectModifier) {
            $select = $baseSelectModifier->execute($select, $storeId);
        }

        return $select;
    }
}

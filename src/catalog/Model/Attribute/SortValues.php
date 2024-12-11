<?php

declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Attribute;

class SortValues
{
    public function execute(array $options): array
    {
        usort($options, [$this, 'sortOptions']);

        return $options;
    }

    /**
     * @param array $a
     * @param array $b
     */
    public function sortOptions($a, $b): int
    {
        $aSizePos = $a['sort_order'] ?? 0;
        $bSizePos = $b['sort_order'] ?? 0;

        if ($aSizePos === $bSizePos) {
            return 0;
        }

        return ($aSizePos > $bSizePos) ? 1 : -1;
    }
}

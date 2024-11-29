<?php

declare(strict_types = 1);

namespace Divante\VsbridgeIndexerCatalog\Model\Attribute;

class SortValues
{
    /**
     * @return array
     */
    public function execute(array $options)
    {
        usort($options, [$this, 'sortOptions']);

        return $options;
    }

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    public function sortOptions($a, $b)
    {
        $aSizePos = $a['sort_order'] ?? 0;
        $bSizePos = $b['sort_order'] ?? 0;

        if ($aSizePos === $bSizePos) {
            return 0;
        }

        return ($aSizePos > $bSizePos) ? 1 : -1;
    }
}

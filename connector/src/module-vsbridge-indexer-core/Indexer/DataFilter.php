<?php

namespace Divante\VsbridgeIndexerCore\Indexer;

class DataFilter
{
    /**
     * @var array
     */
    private $integerProperties = [];

    /**
     * @var array
     */
    private $floatProperties = [];

    public function __construct(
        array $integerProperties = [],
        array $floatProperties = []
    ) {
        $this->integerProperties = $integerProperties;
        $this->floatProperties = $floatProperties;
    }

    /**
     * @param array      $dtoToFilter
     * @param array|null $blackList
     *
     * @return array
     */
    public function execute(array $dtoToFilter, array $blackList = null)
    {
        foreach ($dtoToFilter as $key => $val) {
            if ($blackList && in_array($key, $blackList)) {
                unset($dtoToFilter[$key]);
            } else {
                if (strstr($key, 'is_') || strstr($key, 'has_')) {
                    $dtoToFilter[$key] = (bool)$val;
                } else {
                    if (in_array($key, $this->integerProperties)) {
                        $dtoToFilter[$key] = (int)$val;
                    } elseif (in_array($key, $this->floatProperties)) {
                        $dtoToFilter[$key] = (float)$val;
                    }
                }
            }
        }

        return $dtoToFilter;
    }
}

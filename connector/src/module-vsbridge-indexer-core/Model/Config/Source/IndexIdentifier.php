<?php

namespace Divante\VsbridgeIndexerCore\Model\Config\Source;

class IndexIdentifier implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'id', 'label' => __('Store ID')],
            ['value' => 'code', 'label' => __('Store Code')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['id' => __('Store ID'), 'code' => __('Store Code')];
    }
}

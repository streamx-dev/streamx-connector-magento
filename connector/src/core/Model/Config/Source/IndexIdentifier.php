<?php

namespace StreamX\ConnectorCore\Model\Config\Source;

class IndexIdentifier implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'id', 'label' => __('Store ID')],
            ['value' => 'code', 'label' => __('Store Code')]
        ];
    }

    /**
     * Get options in "key-value" format
     */
    public function toArray(): array
    {
        return ['id' => __('Store ID'), 'code' => __('Store Code')];
    }
}

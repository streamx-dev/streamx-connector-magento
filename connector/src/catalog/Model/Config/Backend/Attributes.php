<?php


declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class Attributes extends Value
{
    /**
     * @inheritdoc
     */
    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            if (in_array('', $this->getValue())) {
                $this->setValue('');
            }
        }

        return parent::beforeSave();
    }
}

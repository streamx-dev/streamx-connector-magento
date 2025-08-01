<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Backend;

use Magento\Framework\App\Config\Value;

class MultiselectFieldValidator extends Value
{
    /**
     * If a user selected an empty value option along with some other options,
     * unselects all the non-empty options, leaving only the empty option selected.
     */
    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            $selectedValues = (array) $this->getValue();
            if (in_array('', $selectedValues)) {
                $this->setValue('');
            }
        }

        return parent::beforeSave();
    }
}

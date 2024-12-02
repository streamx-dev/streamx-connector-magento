<?php

namespace StreamX\ConnectorCatalog\Index\Mapping;

use StreamX\ConnectorCore\Api\Mapping\FieldInterface;
use Magento\Eav\Model\Entity\Attribute;

abstract class AbstractMapping
{
    /**
     * @var array
     */
    private $staticFieldMapping;

    public function __construct(array $staticFieldMapping)
    {
        $this->staticFieldMapping = $staticFieldMapping;
    }

    /**
     * @var array
     */
    private $staticTextMapping = [
        'available_sort_by',
        'default_sort_by',
    ];

    /**
     * Return mapping for an attribute.
     *
     * @param Attribute $attribute Attribute we want the mapping for.
     */
    public function getAttributeMapping(Attribute $attribute): array
    {
        $mapping = [];
        $attributeCode = $attribute->getAttributeCode();
        $type = $this->getAttributeType($attribute);

        if ($this->addKeywordFieldToTextAttribute($attribute, $type)) {
            $mapping[$attributeCode] = [
                'type' => $type,
                'fields' => [
                    'keyword' => [
                        'type' => FieldInterface::TYPE_KEYWORD,
                        'ignore_above' => 256,
                    ]
                ]
            ];
        } elseif ($type === 'date') {
            $mapping[$attributeCode] = [
                'type' => $type,
                'format' => FieldInterface::DATE_FORMAT,
            ];
        } else {
            $mapping[$attributeCode] = ['type' => $type];
        }

        return $mapping;
    }

    /**
     * @return bool
     */
    private function addKeywordFieldToTextAttribute(Attribute $attribute, string $esType)
    {
        $attributeCode = $attribute->getAttributeCode();

        if (FieldInterface::TYPE_TEXT === $esType) {
            if (isset($this->staticTextMapping[$attributeCode])) {
                return false;
            }

            return (!$attribute->getBackendModel() && $attribute->getFrontendInput() != 'media_image');
        }

        return false;
    }

    /**
     * Returns attribute type for indexation.
     */
    public function getAttributeType(Attribute $attribute): string
    {
        $attributeCode = $attribute->getAttributeCode();

        if (isset($this->staticFieldMapping[$attributeCode])) {
            return $this->staticFieldMapping[$attributeCode];
        }

        if (in_array($attributeCode, $this->staticTextMapping)) {
            return FieldInterface::TYPE_TEXT;
        }

        if ($this->isBooleanType($attribute)) {
            return FieldInterface::TYPE_BOOLEAN;
        }

        if ($this->isLongType($attribute)) {
            return FieldInterface::TYPE_LONG;
        }

        if ($this->isDoubleType($attribute)) {
            return FieldInterface::TYPE_DOUBLE;
        }

        if ($this->isDateType($attribute)) {
            return FieldInterface::TYPE_DATE;
        }

        $type = FieldInterface::TYPE_TEXT;

        if ($attribute->usesSource()) {
            $type = $attribute->getSourceModel() ? FieldInterface::TYPE_KEYWORD : FieldInterface::TYPE_LONG;
        }

        return $type;
    }

    /**
     * @return bool
     */
    private function isDateType(Attribute $attribute)
    {
        if ($attribute->getBackendType() == 'datetime' || $attribute->getFrontendInput() === 'date') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isDoubleType(Attribute $attribute)
    {
        if ($attribute->getBackendType() == 'decimal' || $attribute->getFrontendClass() == 'validate-number') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isLongType(Attribute $attribute)
    {
        if ($attribute->getBackendType() == 'int' || $attribute->getFrontendClass() == 'validate-digits') {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isBooleanType(Attribute $attribute)
    {
        $attributeCode = $attribute->getAttributeCode();

        if (substr($attributeCode, 0, 3) === 'is_') {
            return true;
        }
        
        if ($attribute->getSourceModel() == \Magento\Catalog\Model\Product\Attribute\Source\Boolean::class) {
            return true;
        }

        return false;
    }
}

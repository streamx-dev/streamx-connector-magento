<?php

namespace StreamX\ConnectorCatalog\Index\Mapping;

use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;
use Magento\Eav\Model\Entity\Attribute;

abstract class AbstractMapping
{
    private array $staticFieldMapping;

    public function __construct(array $staticFieldMapping)
    {
        $this->staticFieldMapping = $staticFieldMapping;
    }

    private array $staticTextMapping = [
        'available_sort_by',
        'default_sort_by',
    ];

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

    private function addKeywordFieldToTextAttribute(Attribute $attribute, string $esType): bool
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

    private function isDateType(Attribute $attribute): bool
    {
        if ($attribute->getBackendType() == 'datetime' || $attribute->getFrontendInput() === 'date') {
            return true;
        }

        return false;
    }

    private function isDoubleType(Attribute $attribute): bool
    {
        if ($attribute->getBackendType() == 'decimal' || $attribute->getFrontendClass() == 'validate-number') {
            return true;
        }

        return false;
    }

    private function isLongType(Attribute $attribute): bool
    {
        if ($attribute->getBackendType() == 'int' || $attribute->getFrontendClass() == 'validate-digits') {
            return true;
        }

        return false;
    }

    private function isBooleanType(Attribute $attribute): bool
    {
        $attributeCode = $attribute->getAttributeCode();

        if (str_starts_with($attributeCode, 'is_')) {
            return true;
        }
        
        if ($attribute->getSourceModel() == Boolean::class) {
            return true;
        }

        return false;
    }
}

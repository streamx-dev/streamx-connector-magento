<?php

namespace StreamX\ConnectorCore\Indexer;

use StreamX\ConnectorCore\Api\Mapping\FieldInterface;
use StreamX\ConnectorCore\Api\MappingInterface;
use StreamX\ConnectorCore\Api\ConvertValueInterface;

class ConvertValue implements ConvertValueInterface
{
    /**
     * @var array
     */
    private $castMapping = [
        FieldInterface::TYPE_LONG => 'int',
        FieldInterface::TYPE_INTEGER => 'int',
        FieldInterface::TYPE_BOOLEAN => 'bool',
        FieldInterface::TYPE_DOUBLE => 'float',
    ];

    /**
     * @inheritdoc
     */
    public function execute(MappingInterface $mapping, string $field, $value)
    {
        $properties = $mapping->getMappingProperties()['properties'];
        $type = $this->getFieldTypeByCode($properties, $field);

        if (null === $type) {
            return $value;
        }

        if (null === $value) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $v) {
                settype($v, $type);
            }
        } else {
            settype($value, $type);
        }

        return $value;
    }

    private function getFieldTypeByCode(array $mapping, string $field): ?string
    {
        if (isset($mapping[$field]['type'])) {
            $type = $mapping[$field]['type'];

            if (isset($this->castMapping[$type])) {
                return $this->castMapping[$type];
            }

            return null;
        }

        return null;
    }
}

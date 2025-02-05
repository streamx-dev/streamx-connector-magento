<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeOptionSwatchDefinition
{
    private string $type;
    private string $value;

    public function __construct(array $swatch) {
        $this->type = $swatch['type_string'];
        $this->value = $swatch['value'];
    }

    public function getType(): string {
        return $this->type;
    }

    public function getValue(): string {
        return $this->value;
    }
}

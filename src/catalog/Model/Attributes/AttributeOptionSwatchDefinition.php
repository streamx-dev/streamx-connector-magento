<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeOptionSwatchDefinition
{
    private string $type;
    private string $value;

    public function __construct(string $type, string $value) {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getValue(): string {
        return $this->value;
    }
}

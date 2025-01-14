<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeOptionDefinition
{
    private string $value;
    private string $label;

    public function __construct(string $value, string $label) {
        $this->value = $value;
        $this->label = $label;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getLabel(): string {
        return $this->label;
    }
}

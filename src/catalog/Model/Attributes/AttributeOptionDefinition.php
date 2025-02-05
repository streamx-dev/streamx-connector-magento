<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeOptionDefinition
{
    private string $value;
    private string $label;
    private ?AttributeOptionSwatchDefinition $swatch;

    public function __construct(string $value, string $label, ?array $swatch = null) {
        $this->value = $value;
        $this->label = $label;
        $this->swatch = $swatch !== null ? new AttributeOptionSwatchDefinition($swatch) : null;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getLabel(): string {
        return $this->label;
    }

    public function getSwatch(): ?AttributeOptionSwatchDefinition {
        return $this->swatch;
    }
}

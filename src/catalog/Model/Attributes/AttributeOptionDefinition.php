<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use JsonSerializable;

final class AttributeOptionDefinition implements JsonSerializable
{
    private string $value;
    private string $label;
    private ?AttributeOptionSwatchDefinition $swatch;

    public function __construct(string $value, string $label, ?AttributeOptionSwatchDefinition $swatch) {
        $this->value = $value;
        $this->label = $label;
        $this->swatch = $swatch;
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

    public function isSameAs(AttributeOptionDefinition $other): bool {
        if ($this === $other) {
            return true;
        }

        if (($this->swatch === null) != ($other->swatch === null)) { // one of them is null while other is not null
            return false;
        }

        if ($this->swatch !== null && $other->swatch !== null) {
            if (!$this->swatch->isSameAs($other->swatch)) {
                return false;
            }
        }

        return $this->value === $other->value
            && $this->label === $other->label;
    }

    public function jsonSerialize(): array {
        return get_object_vars($this);
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use JsonSerializable;

final class AttributeOptionDefinition implements JsonSerializable
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

    public function isSameAs(AttributeOptionDefinition $other): bool {
        if ($this === $other) {
            return true;
        }

        return $this->getValue() === $other->getValue()
            && $this->getLabel() === $other->getLabel();
    }

    public function jsonSerialize(): array {
        return get_object_vars($this);
    }
}

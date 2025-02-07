<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use JsonSerializable;

final class AttributeOptionSwatchDefinition implements JsonSerializable
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

    public function isSameAs(AttributeOptionSwatchDefinition $other): bool {
        if ($this === $other) {
            return true;
        }

        return $this->type === $other->type
            && $this->value === $other->value;
    }

    public function jsonSerialize(): array {
        return get_object_vars($this);
    }
}

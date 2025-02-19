<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeOptionDefinition
{
    private int $id;
    private string $value;
    private ?AttributeOptionSwatchDefinition $swatch;

    public function __construct(int $id, string $value, ?AttributeOptionSwatchDefinition $swatch) {
        $this->id = $id;
        $this->value = $value;
        $this->swatch = $swatch;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getValue(): string {
        return $this->value;
    }

    public function getSwatch(): ?AttributeOptionSwatchDefinition {
        return $this->swatch;
    }
}

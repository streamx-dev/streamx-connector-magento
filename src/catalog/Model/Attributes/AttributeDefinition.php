<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use JsonSerializable;

final class AttributeDefinition implements JsonSerializable
{
    private int $id;
    private string $name;
    private string $label;
    private bool $isFacet;

    /**
     * @var AttributeOptionDefinition[]
     */
    private array $options;

    /**
     * @param AttributeOptionDefinition[] $options
     */
    public function __construct(int $id, string $name, string $label, bool $isFacet, array $options) {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->isFacet = $isFacet;
        $this->options = $options;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getLabel(): string {
        return $this->label;
    }

    public function isFacet(): bool {
        return $this->isFacet;
    }

    /**
     * @return AttributeOptionDefinition[]
     */
    public function getOptions(): array {
        return $this->options;
    }

    public function getValueLabel(string $attributeValue): string {
        foreach ($this->options as $option) {
            if ($option->getValue() === $attributeValue) {
                return $option->getLabel();
            }
        }
        return $attributeValue;
    }

    public function isSameAs(AttributeDefinition $other): bool {
        if ($this === $other) {
            return true;
        }

        if ($this->id !== $other->id) {
            return false;
        }

        if ($this->name !== $other->name) {
            return false;
        }

        if ($this->label !== $other->label) {
            return false;
        }

        if ($this->isFacet !== $other->isFacet) {
            return false;
        }

        if (count($this->options) != count($other->options)) {
            return false;
        }

        for ($i = 0; $i < count($this->options); $i++) {
            $thisOption = $this->options[$i];
            $otherOption = $other->options[$i];
            if (!($thisOption->isSameAs($otherOption))) {
                return false;
            }
        }

        return true;
    }

    public function jsonSerialize(): array {
        return get_object_vars($this);
    }

    public static function fromJson(string $json): AttributeDefinition {
        $obj = json_decode($json);
        return new AttributeDefinition(
            $obj->id,
            $obj->name,
            $obj->label,
            $obj->isFacet,
            array_map(function ($option) {
                return new AttributeOptionDefinition(
                    $option->value,
                    $option->label,
                    $option->swatch
                );
            }, $obj->options),
        );
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeDefinition
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
}

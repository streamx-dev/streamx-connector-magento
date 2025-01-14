<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeDefinition
{
    private string $name;
    private string $label;
    // TODO: add field: private boolean $isFacet;

    /**
     * @var AttributeOptionDefinition[]
     */
    private array $options;

    /**
     * @param AttributeOptionDefinition[] $options
     */
    public function __construct(string $name, string $label, array $options) {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getLabel(): string {
        return $this->label;
    }

    /**
     * @return AttributeOptionDefinition[]
     */
    public function getOptions(): array {
        return $this->options;
    }
}

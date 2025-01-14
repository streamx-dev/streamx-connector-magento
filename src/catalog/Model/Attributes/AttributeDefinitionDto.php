<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeDefinitionDto
{
    private string $name;
    private string $label;
    // TODO: add attribute options
    // TODO: add isFacet

    public function __construct(string $name, string $label) {
        $this->name = $name;
        $this->label = $label;
    }

    /**
     * @return string attribute_code
     */
    public function getName(): string {
        return $this->name;
    }

    public function getLabel(): string {
        return $this->label;
    }
}

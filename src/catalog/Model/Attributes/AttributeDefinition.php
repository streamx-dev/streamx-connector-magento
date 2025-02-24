<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Attributes;

class AttributeDefinition
{
    private int $id;
    private string $code;
    private string $value;
    private bool $isFacet;

    /**
     * @var AttributeOptionDefinition[]
     */
    private array $options;

    /**
     * @param AttributeOptionDefinition[] $options
     */
    public function __construct(int $id, string $code, string $value, bool $isFacet, array $options) {
        $this->id = $id;
        $this->code = $code;
        $this->value = $value;
        $this->isFacet = $isFacet;
        $this->options = $options;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getCode(): string {
        return $this->code;
    }

    public function getValue(): string {
        return $this->value;
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

    public function getOptionSwatch(int $optionId): ?AttributeOptionSwatchDefinition {
        foreach ($this->options as $option) {
            if ($option->getId() === $optionId) {
                return $option->getSwatch();
            }
        }
        return null;
    }
}

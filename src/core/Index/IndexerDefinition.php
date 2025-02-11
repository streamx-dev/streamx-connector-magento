<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\DataProviderInterface;

class IndexerDefinition
{
    private string $name;

    /** @var DataProviderInterface[] */
    private array $dataProviders;

    public function __construct(string $name, array $dataProviders) {
        $this->name = $name;
        $this->dataProviders = $dataProviders;
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @return DataProviderInterface[]
     */
    public function getDataProviders(): array {
        return $this->dataProviders;
    }
}

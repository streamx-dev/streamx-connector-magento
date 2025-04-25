<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\DataProviderInterface;

class IndexerDefinition
{
    private string $indexerId;

    /** @var DataProviderInterface[] */
    private array $dataProviders;

    public function __construct(string $indexerId, DataProviderInterface... $dataProviders) {
        $this->indexerId = $indexerId;
        $this->dataProviders = $dataProviders;
    }

    public function getIndexerId(): string {
        return $this->indexerId;
    }

    /**
     * @return DataProviderInterface[]
     */
    public function getDataProviders(): array {
        return $this->dataProviders;
    }
}

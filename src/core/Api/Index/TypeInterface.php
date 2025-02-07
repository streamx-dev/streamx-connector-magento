<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api\Index;

use StreamX\ConnectorCore\Api\DataProviderInterface;

interface TypeInterface
{
    public function getName(): string;

    /**
     * @return DataProviderInterface[]
     */
    public function getDataProviders(): array;

    public function getDataProvider(string $name): DataProviderInterface;
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Index;

use Exception;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;

class Type implements TypeInterface
{
    private string $name;
    private array $dataProviders;

    public function __construct(string $name, array $dataProviders)
    {
        $this->name = $name;
        $this->dataProviders = $dataProviders;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getDataProviders(): array
    {
        return $this->dataProviders;
    }

    public function getDataProvider(string $name): DataProviderInterface
    {
        if (!isset($this->dataProviders[$name])) {
            throw new Exception("DataProvider $name does not exists.");
        }

        return $this->dataProviders[$name];
    }
}

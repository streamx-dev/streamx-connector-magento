<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

interface DataProviderInterface
{
    /**
     * @param array<int, array> $indexData key: entity id, value: the entity as array
     */
    public function addData(array &$indexData, int $storeId): void;
}

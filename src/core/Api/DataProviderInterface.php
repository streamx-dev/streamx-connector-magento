<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

abstract class DataProviderInterface
{
    /**
     * @param array $indexData key: entity id, value: the entity as array
     */
    public abstract function addData(array $indexData, int $storeId): array;

    /**
     * @param array $entities array of entities, each entity is an array that must contain "id" field
     */
    public static function addDataToEntities(array $entities, int $storeId, array $dataProviders): array {
        $indexData = [];
        foreach ($entities as $entity) {
            $indexData[$entity['id']] = $entity;
        }
        foreach ($dataProviders as $dataProvider) {
            $indexData = $dataProvider->addData($indexData, $storeId);
        }
        return array_values($indexData);
    }

}

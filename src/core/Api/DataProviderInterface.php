<?php

namespace StreamX\ConnectorCore\Api;

abstract class DataProviderInterface
{
    /**
     * Append data to a list of documents.
     * @param array $indexData key: entity id, value: the entity as array
     */
    public abstract function addData(array $indexData, int $storeId): array;

    /**
     * Append data to a list of documents.
     * @param array $entities array of entities, each entity is an array that must contain "id" field
     */
    public final function addDataToEntities(array $entities, int $storeId): array {
        $indexData = [];
        foreach ($entities as $entity) {
            $indexData[$entity['id']] = $entity;
        }
        $indexData = $this->addData($indexData, $storeId);
        return array_values($indexData);
    }

}

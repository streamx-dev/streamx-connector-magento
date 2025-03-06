<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Traversable;

interface BasicDataLoader
{

    /**
     * @param array $entityIds if empty - loads data for all available IDs for the entity type
     * @return Traversable stream of pairs:
     *  - either [id => entity] if the entity exists and is eligible in the current store
     *  - or [id => [empty array]] if the entity does not exist or is not eligible in the current store
     * TODO consider some object oriented definition for the returned data
     */
    public function loadData(int $storeId, array $entityIds): Traversable;
}

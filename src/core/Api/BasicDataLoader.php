<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Traversable;

interface BasicDataLoader
{

    /**
     * @param array $entityIds if empty - loads data for all available IDs for the entity type
     * @return Traversable stream of pairs:
     *  - either [id => entity] if the entity exists and is eligible in the current store. The entity can be an array or a DTO object
     *  - or [id => null] if the entity does not exist or is not eligible in the current store
     */
    public function loadData(int $storeId, array $entityIds): Traversable;
}

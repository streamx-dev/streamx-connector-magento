<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Traversable;

interface BasicDataLoader
{

    /**
     * @param array $entityIds if empty - loads data for all available IDs for the entity type
     */
    public function loadData(int $storeId, array $entityIds): Traversable;
}

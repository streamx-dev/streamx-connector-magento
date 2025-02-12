<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Traversable;

interface BaseAction
{

    /**
     * Loads basic data for the entities by IDs.
     * @param array $entityIds if empty - loads data for all available IDs for the entity type
     */
    public function loadData(int $storeId, array $entityIds): Traversable;
}

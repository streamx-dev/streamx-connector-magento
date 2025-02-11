<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Traversable;

interface BaseAction
{

    /**
     * @param array $entityIds if empty - loads data for all available IDs for the entity type
     */
    public function loadData(int $storeId = 1, array $entityIds = []): Traversable;
}

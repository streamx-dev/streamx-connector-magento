<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Traversable;

interface BaseAction
{

    /**
     * @param int $storeId
     * @param array $entityIds if empty - loads data for all available IDs for the entity type
     * @return Traversable
     */
    public function loadData(int $storeId = 1, array $entityIds = []): Traversable;
}

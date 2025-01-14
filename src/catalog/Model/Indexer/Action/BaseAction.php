<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Traversable;

interface BaseAction
{
    public function loadData(int $storeId = 1, array $entityIds = []): Traversable;
}

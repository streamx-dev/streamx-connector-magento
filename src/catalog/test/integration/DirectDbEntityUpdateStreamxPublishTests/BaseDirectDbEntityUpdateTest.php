<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritdoc
 */
abstract class BaseDirectDbEntityUpdateTest extends BaseStreamxPublishTest {

    protected abstract function indexerName(): string;

    private function viewId(): string {
        return $this->indexerName(); // note: assuming that every indexer in the indexer.xml file has the same value of id and view_id fields
    }

    protected function desiredIndexerMode(): string {
        // Magento creates triggers to save db-level changes only when the scheduler is in the below mode:
        return MagentoIndexerOperationsExecutor::UPDATE_BY_SCHEDULE_DISPLAY_NAME;
    }

    protected function reindexMview() {
        $this->callMagentoEndpoint('mview/reindex', [
            'indexerViewId' => $this->viewId()
        ]);
    }
}
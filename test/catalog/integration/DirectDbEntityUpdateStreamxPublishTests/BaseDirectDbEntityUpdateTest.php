<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;

/**
 * @inheritdoc
 * @UpdateByScheduleIndexerMode
 */
abstract class BaseDirectDbEntityUpdateTest extends BaseStreamxConnectorPublishTest {

    private function viewId(): string {
        return self::$testedIndexerName; // note: assuming that every indexer in the indexer.xml file has the same value of id and view_id fields
    }

    protected function reindexMview(): void {
        self::callMagentoPutEndpoint('mview/reindex', [
            'indexerViewId' => $this->viewId()
        ]);
    }
}
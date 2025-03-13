<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UpdateByScheduleIndexerMode
 */
abstract class BaseDirectDbEntityUpdateTest extends BaseStreamxConnectorPublishTest {

    private function viewId(): string {
        return self::$testedIndexerName; // note: assuming that every indexer in the indexer.xml file has the same value of id and view_id fields
    }

    protected function reindexMview(): void {
        $coverage = MagentoEndpointsCaller::call('mview/reindex', [
            'indexerViewId' => $this->viewId()
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
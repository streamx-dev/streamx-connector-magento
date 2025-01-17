<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritdoc
 */
abstract class BaseDirectDbEntityUpdateTest extends BaseStreamxConnectorPublishTest {

    protected abstract function indexerName(): string;

    private function indexerChangelogTableName(): string {
        return $this->indexerName() . '_cl';
    }

    protected function desiredIndexerMode(): string {
        // Magento creates triggers to save db-level changes only when the scheduler is in the below mode:
        return MagentoIndexerOperationsExecutor::UPDATE_BY_SCHEDULE_DISPLAY_NAME;
    }

    protected function reindexMview(): void {
        $coverage = $this->callMagentoPutEndpoint('mview/reindex', [
            'indexerViewId' => $this->viewId()
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }

    public function tearDown(): void {
        $tableName = $this->indexerChangelogTableName();
        $this->db->executeAll([
            "TRUNCATE TABLE $tableName;",
            "ALTER TABLE $tableName AUTO_INCREMENT = 1;"
        ]);

        parent::tearDown();
    }
}
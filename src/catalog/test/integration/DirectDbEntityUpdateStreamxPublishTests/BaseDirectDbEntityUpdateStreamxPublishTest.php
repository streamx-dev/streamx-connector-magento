<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritdoc
 */
abstract class BaseDirectDbEntityUpdateStreamxPublishTest extends BaseStreamxPublishTest {

    protected abstract function indexerName(): string;

    protected function desiredIndexerMode(): string {
        // Magento creates triggers to save db-level changes only when the scheduler is in the below mode:
        return MagentoIndexerOperationsExecutor::UPDATE_BY_SCHEDULE_DISPLAY_NAME;
    }
}
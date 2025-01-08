<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritdoc
 */
abstract class BaseAppEntityUpdateTest extends BaseStreamxConnectorPublishTest {

    protected abstract function indexerName(): string;

    protected function desiredIndexerMode(): string {
        return MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME;
    }
}
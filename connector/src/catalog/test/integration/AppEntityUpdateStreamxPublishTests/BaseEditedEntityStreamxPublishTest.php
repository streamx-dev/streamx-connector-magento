<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritdoc
 */
abstract class BaseEditedEntityStreamxPublishTest extends BaseStreamxPublishTest {

    protected const REST_API_BASE_URL = 'https://magento.test/rest/all/V1';

    protected abstract function indexerName(): string;

    protected function desiredIndexerMode(): string {
        return MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME;
    }
}
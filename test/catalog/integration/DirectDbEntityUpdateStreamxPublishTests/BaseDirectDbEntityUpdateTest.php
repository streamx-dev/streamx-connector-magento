<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;

/**
 * @inheritdoc
 */
abstract class BaseDirectDbEntityUpdateTest extends BaseStreamxConnectorPublishTest {
    const INDEXER_MODE = parent::UPDATE_BY_SCHEDULE;
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;

/**
 * @inheritdoc
 */
abstract class BaseAppEntityUpdateTest extends BaseStreamxConnectorPublishTest {
    const INDEXER_MODE = parent::UPDATE_ON_SAVE;
}
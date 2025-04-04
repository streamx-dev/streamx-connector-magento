<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxConnectorPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UpdateByScheduleIndexerMode
 */
abstract class BaseDirectDbEntityUpdateTest extends BaseStreamxConnectorPublishTest {

    protected function reindexMview(): void {
        foreach (self::$testedIndexerNames as $testedIndexerName) {
            MagentoEndpointsCaller::call('mview/reindex', [
                // note: assuming that every indexer in the indexer.xml file shares its ID with its view ID
                'indexerViewId' => $testedIndexerName
            ]);
        }
    }
}
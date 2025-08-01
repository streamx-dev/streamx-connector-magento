<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\BaseStreamxTest;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationKeyPaths;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use StreamX\ConnectorCore\Config\GeneralConfigObserver;

class BatchIndexingSizeLimitTest extends BaseStreamxTest {

    /** @test */
    public function shouldNotAllowTooHighBatchIndexingSize() {
        try {
            // when: set acceptable value
            $this->attemptSettingBatchIndexingSize(200);

            // then
            $this->assertCurrentBatchIndexingSize(200);

            // when: attempt to set a too high value
            $this->attemptSettingBatchIndexingSize(1000);

            // then: expect value to be forced to default
            $this->assertCurrentBatchIndexingSize(100);
        } finally {
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationKeyPaths::BATCH_INDEXING_SIZE);
        }
    }

    private function attemptSettingBatchIndexingSize(int $size): void {
        ConfigurationEditUtils::setConfigurationValue(ConfigurationKeyPaths::BATCH_INDEXING_SIZE, $size);

        MagentoEndpointsCaller::call('observers/execute', [
            'observerClassName' => GeneralConfigObserver::class
        ]);
    }

    private function assertCurrentBatchIndexingSize(int $expectedValue): void {
        $db = new MagentoMySqlQueryExecutor();
        $actualValue = (int) $db->selectSingleValue("
            SELECT value
              FROM core_config_data
             WHERE path='" . ConfigurationKeyPaths::BATCH_INDEXING_SIZE . "'
        ");
        $db->disconnect();

        $this->assertEquals($expectedValue, $actualValue);
    }
}
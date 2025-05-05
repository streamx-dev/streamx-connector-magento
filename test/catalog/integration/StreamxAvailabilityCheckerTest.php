<?php

namespace StreamX\ConnectorCatalog\test\integration;

use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use StreamX\ConnectorCore\Client\StreamxAvailabilityChecker;

class StreamxAvailabilityCheckerTest extends BaseStreamxTest {

    use ValidationFileUtils;

    private const STORE_ID = 1;
    private const STORE_CODE = 'store_1';

    private const NOT_EXISTING_HOST = 'c793qwh0uqw3fg94ow';
    private const WRONG_INGESTION_PORT = 1234;

    /** @test */
    public function streamxShouldBeAvailable() {
        // given
        $restIngestionUrl = parent::STREAMX_REST_INGESTION_URL;

        // when
        $checker = $this->createChecker($restIngestionUrl);

        // then
        $this->assertTrue($checker->isStreamxAvailable());
    }

    /** @test */
    public function streamxShouldNotBeAvailable_WhenNotExistingHost() {
        // given
        $restIngestionUrl = self::changedRestIngestionUrl('host', self::NOT_EXISTING_HOST);

        // when
        $checker = $this->createChecker($restIngestionUrl);

        // then
        $this->assertFalse($checker->isStreamxAvailable());
    }

    /** @test */
    public function streamxShouldNotBeAvailable_WhenWrongPort() {
        // given
        $restIngestionUrl = self::changedRestIngestionUrl('port', self::WRONG_INGESTION_PORT);

        // when
        $checker = $this->createChecker($restIngestionUrl);

        // then
        $this->assertFalse($checker->isStreamxAvailable());
    }

    private function createChecker(string $restIngestionUrl): StreamxAvailabilityChecker {
        $loggerMock = $this->createLoggerMock();
        $clientConfigurationMock = $this->createClientConfigurationMock($restIngestionUrl);
        return new StreamxAvailabilityChecker($loggerMock, $clientConfigurationMock, self::STORE_ID);
    }

    private static function changedRestIngestionUrl(string $urlPartName, $newValue): string {
        $parsedUrl = parse_url(parent::STREAMX_REST_INGESTION_URL);
        $oldValue = $parsedUrl[$urlPartName];
        return str_replace($oldValue, $newValue, parent::STREAMX_REST_INGESTION_URL);
    }
}
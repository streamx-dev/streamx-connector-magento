<?php

namespace StreamX\ConnectorCatalog\test\integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * Prerequisites to run these tests:
 * 1. markshust/docker-magento images must be running
 * 2. StreamX Connector must be deployed to the Magento instance
 * 3. StreamX Connector must be enabled and configured in Magento
 * 4. StreamX must be running (with the add-rest-ingestion-to-magento-network.sh script executed)
 */
abstract class BaseStreamxPublishTest extends TestCase {

    private const STREAMX_DELIVERY_SERVICE_BASE_URL = "http://localhost:8081";
    private const STREAMX_REST_INGESTION_URL = "http://localhost:8080";
    private const MAGENTO_REST_API_BASE_URL = 'https://magento.test/rest/all/V1';

    private const CHANNEL_SCHEMA_NAME = "dev.streamx.blueprints.data.DataIngestionMessage";
    private const CHANNEL_NAME = "data";
    private const DATA_PUBLISH_TIMEOUT_SECONDS = 3;

    protected MagentoIndexerOperationsExecutor $indexerOperations;
    private string $originalIndexerMode;
    private bool $indexModeNeedsRestoring;

    protected abstract function indexerName(): string;
    protected abstract function desiredIndexerMode(): string;

    public function setUp(): void {
        $this->indexerOperations = new MagentoIndexerOperationsExecutor($this->indexerName());
        $this->originalIndexerMode = $this->indexerOperations->getIndexerMode();

        if ($this->desiredIndexerMode() !== $this->originalIndexerMode) {
            $this->indexerOperations->setIndexerMode($this->desiredIndexerMode());
            $this->indexModeNeedsRestoring = true;
        } else {
            $this->indexModeNeedsRestoring = false;
        }
    }

    public function tearDown(): void {
        if ($this->indexModeNeedsRestoring) {
            $this->indexerOperations->setIndexerMode($this->originalIndexerMode);
        }
    }

    protected function assertDataIsPublished(string $key, string $contentSubstring): void {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        $response = null;
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                echo "Published content: $response\n";
                if (str_contains($response, $contentSubstring)) {
                    $this->assertTrue(true); // needed to work around the "This test did not perform any assertions" warning
                    return;
                }
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        if ($response !== false) {
            $this->assertStringContainsString($contentSubstring, $response);
        } else {
            $this->fail("$url: not found");
        }
    }

    protected function assertDataIsUnpublished(string $key): void {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if (empty($response)) {
                $this->assertTrue(true); // needed to work around the "This test did not perform any assertions" warning
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: exists");
    }

    protected function removeFromStreamX(string $key): void {
        StreamxClientBuilders::create(self::STREAMX_REST_INGESTION_URL)
            ->build()
            ->newPublisher(self::CHANNEL_NAME, self::CHANNEL_SCHEMA_NAME)
            ->unpublish($key);
    }

    protected function callMagentoEndpoint(string $relativeUrl, array $params): string {
        $endpointUrl = self::MAGENTO_REST_API_BASE_URL . '/' . $relativeUrl;
        $jsonBody = json_encode($params);
        $headers = ['Content-Type' => 'application/json; charset=UTF-8'];

        $request = new Request('PUT', $endpointUrl, $headers, $jsonBody);
        $httpClient = new Client(['verify' => false]);
        $response = $httpClient->sendRequest($request);
        $responseBody = (string)$response->getBody();
        $this->assertEquals(200, $response->getStatusCode(), $responseBody);

        return $responseBody;
    }
}
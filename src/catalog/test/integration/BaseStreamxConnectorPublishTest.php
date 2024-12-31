<?php

namespace StreamX\ConnectorCatalog\test\integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritDoc
 */
abstract class BaseStreamxConnectorPublishTest extends BaseStreamxTest {

    private const MAGENTO_REST_API_BASE_URL = 'https://magento.test/rest/all/V1';

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
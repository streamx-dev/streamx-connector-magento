<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use StreamX\ConnectorCatalog\test\integration\BaseStreamxPublishTest;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * @inheritdoc
 */
abstract class BaseAppEntityUpdateStreamxPublishTest extends BaseStreamxPublishTest {

    private const REST_API_BASE_URL = 'https://magento.test/rest/all/V1';

    protected abstract function indexerName(): string;

    protected function desiredIndexerMode(): string {
        return MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME;
    }

    protected function callRestApiEndpoint(string $relativeUrl, array $params): void {
        $endpointUrl = self::REST_API_BASE_URL . '/' . $relativeUrl;
        $jsonBody = json_encode($params);
        $headers = ['Content-Type' => 'application/json; charset=UTF-8'];

        $request = new Request('PUT', $endpointUrl, $headers, $jsonBody);
        $httpClient = new Client([ 'verify' => false ]);
        $response = $httpClient->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
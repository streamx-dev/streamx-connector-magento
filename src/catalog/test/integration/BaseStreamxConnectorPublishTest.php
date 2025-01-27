<?php

namespace StreamX\ConnectorCatalog\test\integration;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;

/**
 * @inheritDoc
 */
abstract class BaseStreamxConnectorPublishTest extends BaseStreamxTest {

    private const MAGENTO_REST_API_BASE_URL = 'https://magento.test:444/rest/all/V1';

    protected MagentoIndexerOperationsExecutor $indexerOperations;
    private string $originalIndexerMode;
    private bool $indexModeNeedsRestoring;

    protected MagentoMySqlQueryExecutor $db;

    protected abstract function indexerName(): string;
    protected abstract function desiredIndexerMode(): string;

    protected function viewId(): string {
        return $this->indexerName(); // note: assuming that every indexer in the indexer.xml file has the same value of id and view_id fields
    }

    public function setUp(): void {
        $this->setUpIndexerTool();
        $this->setUpDbTool();
    }

    public function tearDown(): void {
        $this->resetIndexer();
        $this->tearDownIndexerTool();
        $this->tearDownDbTool();
    }

    private function setUpIndexerTool(): void {
        $this->indexerOperations = new MagentoIndexerOperationsExecutor($this->indexerName());
        $this->originalIndexerMode = $this->indexerOperations->getIndexerMode();

        if ($this->desiredIndexerMode() !== $this->originalIndexerMode) {
            $this->indexerOperations->setIndexerMode($this->desiredIndexerMode());
            $this->indexModeNeedsRestoring = true;
        } else {
            $this->indexModeNeedsRestoring = false;
        }
    }

    private function tearDownIndexerTool(): void {
        if ($this->indexModeNeedsRestoring) {
            $this->indexerOperations->setIndexerMode($this->originalIndexerMode);
        }
    }

    private function setUpDbTool(): void {
        $this->db = new MagentoMySqlQueryExecutor();
        $this->db->connect();
    }

    private function tearDownDbTool(): void {
        $this->db->disconnect();
    }

    private function resetIndexer(): void {
        $this->db->execute("
            UPDATE mview_state
               SET mode = 'disabled',
                   status = 'idle'
             WHERE view_id='{$this->viewId()}'
        ");
    }

    protected function callMagentoPutEndpoint(string $relativeUrl, array $params): string {
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

    protected function allowIndexingAllAttributes(): void {
        $this->indexerOperations->replaceTextInMagentoFile(
            'src/catalog/Model/SystemConfig/CatalogConfig.php',
            'return explode(',
            'return []; // explode('
        );
    }

    protected function restoreDefaultIndexingAttributes(): void {
        $this->indexerOperations->replaceTextInMagentoFile(
            'src/catalog/Model/SystemConfig/CatalogConfig.php',
            'return []; // explode(',
            'return explode(',
        );
    }
}
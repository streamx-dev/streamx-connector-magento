<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class AppProductUpdateStreamxPublishTest extends BaseAppEntityUpdateStreamxPublishTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductEditedUsingMagentoApplicationToStreamx() {
        // given
        $productOldName = 'Joust Duffle Bag';
        $productNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $productId = MagentoMySqlQueryExecutor::getProductId($productOldName);

        // when
        self::renameProductUsingEndpoint($productId, $productNewName);

        // then
        $expectedKey = "product_$productId";
        try {
            $this->assertDataIsPublished($expectedKey, $productNewName);
        } finally {
            self::renameProductUsingEndpoint($productId, $productOldName);
            $this->assertDataIsPublished($expectedKey, $productOldName);
        }
    }

    private function renameProductUsingEndpoint(int $productId, string $newName) {
        $endpointUrl = self::REST_API_BASE_URL .'/product/rename';
        $jsonBody = json_encode([
            'productId' => $productId,
            'newName' => $newName
        ]);

        $request = new Request('PUT', $endpointUrl, ['Content-Type' => 'application/json; charset=UTF-8'], $jsonBody);
        $httpClient = new Client([ 'verify' => false ]);
        $response = $httpClient->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
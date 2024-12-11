<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

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
        self::renameProduct($productId, $productNewName);

        // then
        $expectedKey = "product_$productId";
        try {
            $this->assertDataIsPublished($expectedKey, $productNewName);
        } finally {
            self::renameProduct($productId, $productOldName);
            $this->assertDataIsPublished($expectedKey, $productOldName);
        }
    }

    private function renameProduct(int $productId, string $newName) {
        $this->callRestApiEndpoint('product/rename', [
            'productId' => $productId,
            'newName' => $newName
        ]);
    }
}
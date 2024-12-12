<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class AppProductAddStreamxPublishTest extends BaseAppEntityUpdateStreamxPublishTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductAddedUsingMagentoApplicationToStreamx() {
        // given
        $productName = 'The new great watch!';
        $categoryId = MagentoMySqlQueryExecutor::getCategoryId('Watches');

        // when
        $productId = self::addProduct($productName, $categoryId);

        // then
        $expectedKey = "product_$productId";
        try {
            $this->assertDataIsPublished($expectedKey, $productName);
        } finally {
            self::deleteProduct($productId);
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    private function addProduct(string $productName, int $categoryId): int {
        return (int) $this->callRestApiEndpoint('product/add', [
            'productName' => $productName,
            'categoryId' => $categoryId
        ]);
    }

    private function deleteProduct(int $productId): void {
        $this->callRestApiEndpoint('product/delete', [
            'productId' => $productId
        ]);
    }
}
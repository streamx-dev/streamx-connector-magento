<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class ProductAddAndDeleteTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedProduct() {
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
            // and when
            self::deleteProduct($productId);

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    private function addProduct(string $productName, int $categoryId): int {
        return (int) $this->callMagentoEndpoint('product/add', [
            'productName' => $productName,
            'categoryId' => $categoryId
        ]);
    }

    private function deleteProduct(int $productId): void {
        $this->callMagentoEndpoint('product/delete', [
            'productId' => $productId
        ]);
    }
}
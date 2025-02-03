<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

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
        $productName = 'The new great watch';
        $categoryId = $this->db->getCategoryId('Watches');

        // when
        $this->allowIndexingAllAttributes();
        $productId = self::addProduct($productName, $categoryId);

        // then
        $expectedKey = "pim:$productId";
        try {
            $this->assertExactDataIsPublished($expectedKey, 'added-watch-product-without-custom-options.json', [
                '"id": [0-9]+' => '"id": 0',
                '"sku": "[^"]+"' => '"sku": "[MASKED]"',
                '"the-new-great-watch-[0-9]+"' => '"the-new-great-watch-0"'
            ]);
        } finally {
            try {
                // and when
                self::deleteProduct($productId);

                // then
                $this->assertDataIsUnpublished($expectedKey);
            } finally {
                $this->restoreDefaultIndexingAttributes();
            }
        }
    }

    private function addProduct(string $productName, int $categoryId): int {
        return (int) $this->callMagentoPutEndpoint('product/add', [
            'productName' => $productName,
            'categoryId' => $categoryId
        ]);
    }

    private function deleteProduct(int $productId): void {
        $this->callMagentoPutEndpoint('product/delete', [
            'productId' => $productId
        ]);
    }
}
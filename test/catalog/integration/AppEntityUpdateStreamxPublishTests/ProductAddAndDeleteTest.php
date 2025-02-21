<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductAddAndDeleteTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedProduct() {
        // given
        $productName = 'The new great watch';
        $categoryIds = [
            self::$db->getCategoryId('Watches'),
            self::$db->getCategoryId('Collections'), // note: this category is not active in sample data by default
            self::$db->getCategoryId('Sale')
        ];

        // when
        $this->allowIndexingAllAttributes();
        $productId = self::addProduct($productName, $categoryIds);

        // then
        $expectedKey = "pim:$productId";
        try {
            $publishedJson = $this->assertExactDataIsPublished($expectedKey, 'added-watch-product-without-custom-options.json', [
                // mask variable parts (ids and generated sku)
                '"id": [0-9]+' => '"id": 0',
                '"sku": "[^"]+"' => '"sku": "[MASKED]"',
                '"the-new-great-watch-[0-9]+"' => '"the-new-great-watch-0"'
            ]);

            // and
            $this->assertStringContainsString('Watches', $publishedJson);
            $this->assertStringNotContainsString('Collections', $publishedJson);
            $this->assertStringContainsString('Sale', $publishedJson);
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

    private function addProduct(string $productName, array $categoryIds): int {
        return (int) self::callMagentoPutEndpoint('product/add', [
            'productName' => $productName,
            'categoryIds' => $categoryIds
        ]);
    }

    private function deleteProduct(int $productId): void {
        self::callMagentoPutEndpoint('product/delete', [
            'productId' => $productId
        ]);
    }
}
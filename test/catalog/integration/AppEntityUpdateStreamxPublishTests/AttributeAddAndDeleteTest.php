<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesAttributeIndexer
 */
class AttributeAddAndDeleteTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedUsingMagentoApplicationToStreamx() {
        // given
        $attributeCode = 'the_new_attribute';
        $productId = self::$db->getProductId('Sprite Foam Roller');

        // and
        $expectedKey = "pim:$productId";
        $this->removeFromStreamX($expectedKey);

        // when
        $this->allowIndexingAllAttributes();
        $attributeId = self::addAttribute($attributeCode, $productId);

        try {
            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product-with-custom-attribute.json', [], true);
        } finally {
            try {
                // and when
                self::deleteAttribute($attributeId);

                // then
                // note: we don't implement code to retrieve (and republish) product that used a deleted attribute, so the product is not republished, its last published version still has the custom attribute:
                $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product-with-custom-attribute.json', [], true);
            } finally {
                $this->restoreDefaultIndexingAttributes();
            }
        }
    }

    private function addAttribute(string $attributeCode, int $productId): int {
        return (int) self::callMagentoPutEndpoint('attribute/add', [
            'attributeCode' => $attributeCode,
            'productId' => $productId
        ]);
    }

    private function deleteAttribute(int $attributeId): void {
        self::callMagentoPutEndpoint('attribute/delete', [
            'attributeId' => $attributeId
        ]);
    }
}
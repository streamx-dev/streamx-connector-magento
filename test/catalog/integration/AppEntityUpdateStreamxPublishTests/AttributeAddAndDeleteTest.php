<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

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
        $expectedKey = self::productKey($productId);
        $this->removeFromStreamX($expectedKey);

        // when
        $this->setIndexedProductAttributes($attributeCode);
        $attributeId = self::addAttributeAndAssignToProduct($attributeCode, $productId);

        try {
            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product-with-custom-attribute.json');
        } finally {
            try {
                // and when
                self::deleteAttribute($attributeId);

                // then
                // note: we don't implement code to retrieve (and republish) product that used a deleted attribute, so the product is not republished, its last published version still has the custom attribute:
                $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product-with-custom-attribute.json');
            } finally {
                $this->restoreDefaultIndexedProductAttributes();
            }
        }
    }

    private function addAttributeAndAssignToProduct(string $attributeCode, EntityIds $productId): int {
        return (int) self::callMagentoPutEndpoint('attribute/add-and-assign', [
            'attributeCode' => $attributeCode,
            'productId' => $productId->getEntityId()
        ]);
    }

    private function deleteAttribute(int $attributeId): void {
        self::callMagentoPutEndpoint('attribute/delete', [
            'attributeId' => $attributeId
        ]);
    }
}
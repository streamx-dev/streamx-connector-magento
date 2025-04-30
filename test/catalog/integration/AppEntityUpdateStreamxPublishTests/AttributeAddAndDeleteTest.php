<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [ProductProcessor::INDEXER_ID];

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedUsingMagentoApplication() {
        // given
        $attributeCode = 'the_new_attribute';
        $productId = self::$db->getProductId('Sprite Foam Roller');

        // and
        $expectedKey = self::productKey($productId);
        $this->removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::setIndexedProductAttributes('the_new_attribute');
        $attributeId = self::addAttributeAndAssignToProduct($attributeCode, $productId);

        try {
            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product.json');
        } finally {
            try {
                // and when
                self::deleteAttribute($attributeId);

                // then
                $this->assertExactDataIsPublished($expectedKey, 'original-roller-product.json');
            } finally {
                ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
            }
        }
    }

    private function addAttributeAndAssignToProduct(string $attributeCode, EntityIds $productId): int {
        return (int) MagentoEndpointsCaller::call('attribute/add-and-assign', [
            'attributeCode' => $attributeCode,
            'productId' => $productId->getEntityId()
        ]);
    }

    private function deleteAttribute(int $attributeId): void {
        MagentoEndpointsCaller::call('attribute/delete', [
            'attributeId' => $attributeId
        ]);
    }
}
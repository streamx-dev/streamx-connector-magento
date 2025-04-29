<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [ProductIndexer::INDEXER_ID];

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedUsingMagentoApplication_AndUnpublishAtAttributeDeletion() {
        $this->verifyProductIngestionOnAddAndDeleteAttribute(false);
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedUsingMagentoApplication_AndNotUnpublishWhenTheAttributeIsNotIndexed() {
        $this->verifyProductIngestionOnAddAndDeleteAttribute(true);
    }

    private function verifyProductIngestionOnAddAndDeleteAttribute(bool $unsetIndexingAttributeBeforeDeletingAttribute) {
        // given
        $attributeCode = 'the_new_attribute';
        $productId = self::$db->getProductId('Sprite Foam Roller');

        // and
        $expectedKey = self::productKey($productId);
        $this->removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::addIndexedProductAttributes($attributeCode);
        $attributeId = self::addAttributeAndAssignToProduct($attributeCode, $productId);

        try {
            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product.json');
        } finally {
            try {
                // and when
                if ($unsetIndexingAttributeBeforeDeletingAttribute) {
                    ConfigurationEditUtils::unsetIndexedProductAttribute($attributeCode);
                }
                self::deleteAttribute($attributeId);

                // then
                if ($unsetIndexingAttributeBeforeDeletingAttribute) {
                    // expecting the product republish to not be triggered when an unindexed attribute is deleted
                    usleep(200_000);
                    $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product.json');
                } else {
                    // expecting the product to be republished (and without the delete attribute in payload)
                    $this->assertExactDataIsPublished($expectedKey, 'original-roller-product.json');
                }
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
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductVariantIngestionWithFlagSetToIncludeInvisibleProductsTest extends BaseMultistoreProductVariantIngestionTest {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        ConfigurationEditUtils::setConfigurationValue(ConfigurationEditUtils::EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY_PATH, '1');
    }

    public static function tearDownAfterClass(): void {
        ConfigurationEditUtils::restoreConfigurationValue(ConfigurationEditUtils::EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY_PATH);
        parent::tearDownAfterClass();
    }

    /** @test */
    public function verifyIngestion_OnDisableVariant() {
        // given
        $this->disableProductInStore2(self::$variant2);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store1Id, [self::$variant1, self::$variant2]);

        // - parent is still in store 2 but now contains only variant 1, since variant 2 was disabled
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store2Id, [self::$variant1]);

        // - both variants are still published in store 1
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1, self::$variant2]);

        // - variant 2 was disabled in store 2 - should be unpublished
        $this->assertSeparatelyPublishedVariants(self::$store2Id, [self::$variant1]);
    }

    /** @test */
    public function verifyIngestion_OnDisableParent() {
        // given
        $this->disableProductInStore2(self::$parent);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store1Id, [self::$variant1, self::$variant2]);

        // - parent is unpublished from store 2
        $this->assertParentIsNotPublished(self::$store2Id);

        // - both variants are still in both stores
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1, self::$variant2]);
        $this->assertSeparatelyPublishedVariants(self::$store2Id, [self::$variant1, self::$variant2]);
    }

    /** @test */
    public function verifyIngestion_OnMakeVariantInvisible() {
        // given
        $this->makeProductInvisibleInStore2(self::$variant2);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store1Id, [self::$variant1, self::$variant2]);

        // - parent is still in store 2 and contains both variants (invisible variants are always published in the parent's payload)
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store2Id, [self::$variant1, self::$variant2]);

        // - both variants are still in both stores, due to the flag to export also invisible products is turned on
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1, self::$variant2]);
        $this->assertSeparatelyPublishedVariants(self::$store2Id, [self::$variant1, self::$variant2]);
    }

    /** @test */
    public function verifyIngestion_OnMakeParentInvisible() {
        // given
        $this->makeProductInvisibleInStore2(self::$parent);

        // when
        $this->reindexMview();

        // then
        // - parent is still in both stores, due to the flag to export also invisible products is turned on
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store1Id, [self::$variant1, self::$variant2]);
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store2Id, [self::$variant1, self::$variant2]);

        // - both variants are still in both stores, due to the flag to export also invisible products is turned on
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1, self::$variant2]);
        $this->assertSeparatelyPublishedVariants(self::$store2Id, [self::$variant1, self::$variant2]);
    }
}
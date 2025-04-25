<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationKeyPaths;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductVariantIngestionWithFlagSetToIncludeInvisibleProductsTest extends BaseMultistoreProductVariantIngestionTest {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        ConfigurationEditUtils::setConfigurationValue(ConfigurationKeyPaths::EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY, '1');
    }

    public static function tearDownAfterClass(): void {
        ConfigurationEditUtils::restoreConfigurationValue(ConfigurationKeyPaths::EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY);
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
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore1);

        // - parent is still in store 2 but now contains only variant 1, since variant 2 was disabled
        $this->assertParentIsPublishedWithoutVariant2InPayload(self::$keyOfParentInStore2);

        // - both variants are still published in store 1
        $this->assertVariantIsPublished(self::$keyOfVariant1InStore1);
        $this->assertVariantIsPublished(self::$keyOfVariant2InStore1);

        // - variant 2 was disabled in store 2 - should be unpublished
        $this->assertVariantIsPublished(self::$keyOfVariant1InStore2);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore2);

        // revert changes
        $this->enableProductInStore2(self::$variant2);
    }

    /** @test */
    public function verifyIngestion_OnDisableParent() {
        // given
        $this->disableProductInStore2(self::$parent);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore1);

        // - parent is unpublished from store 2
        $this->assertDataIsNotPublished(self::$keyOfParentInStore2);

        // - both variants are still in both stores
        $this->assertVariantsArePublished(
            self::$keyOfVariant1InStore1,
            self::$keyOfVariant2InStore1,
            self::$keyOfVariant1InStore2,
            self::$keyOfVariant2InStore2
        );

        // revert changes
        $this->enableProductInStore2(self::$parent);
    }

    /** @test */
    public function verifyIngestion_OnMakeVariantInvisible() {
        // given
        self::$db->productDummyUpdate(self::$variant2); // variants are not visible by default, so use a dummy update

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore1);

        // - parent is still in store 2 and contains both variants (invisible variants are always published in the parent's payload)
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore2);

        // - both variants are still in both stores, due to the flag to export also invisible products is turned on
        $this->assertVariantsArePublished(
            self::$keyOfVariant1InStore1,
            self::$keyOfVariant2InStore1,
            self::$keyOfVariant1InStore2,
            self::$keyOfVariant2InStore2
        );

        // revert changes
        self::$db->revertProductDummyUpdate(self::$variant2);
    }

    /** @test */
    public function verifyIngestion_OnMakeParentInvisible() {
        // given
        $this->makeParentProductInvisibleInStore2();

        // when
        $this->reindexMview();

        // then
        // - parent is still in both stores, due to the flag to export also invisible products is turned on
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore1);
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore2);

        // - both variants are still in both stores, due to the flag to export also invisible products is turned on
        $this->assertVariantsArePublished(
            self::$keyOfVariant1InStore1,
            self::$keyOfVariant2InStore1,
            self::$keyOfVariant1InStore2,
            self::$keyOfVariant2InStore2
        );

        // revert changes
        $this->makeParentProductVisibleInStore2();
    }
}
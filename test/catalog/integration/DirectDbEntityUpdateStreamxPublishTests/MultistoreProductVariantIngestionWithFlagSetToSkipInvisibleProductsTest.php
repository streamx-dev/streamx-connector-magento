<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 */
class MultistoreProductVariantIngestionWithFlagSetToSkipInvisibleProductsTest extends BaseMultistoreProductVariantIngestionTest {

    /** @test */
    public function verifyIngestion_OnDisableVariant() {
        // given
        $this->disableProductInStore2(self::$variant2);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore1);

        // - parent is still in store 2 but now contains only variant 1
        $this->assertParentIsPublishedWithoutVariant2InPayload(self::$keyOfParentInStore2);

        // - variant is still published in store 1, but editing variant 2 triggered reindexing it in all stores, and the variant is not visible in store 1 - so it was unpublished
        $this->assertVariantIsPublished(self::$keyOfVariant1InStore1);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore1);

        // - variant is still published in store 2, but since it was disabled in store 2 - it's unpublished
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
        $this->assertDataIsNotPublished(self::$store2Id);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertVariantIsNotPublished(self::$keyOfVariant1InStore1);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore1);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertVariantIsNotPublished(self::$keyOfVariant1InStore2);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore2);

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

        // - variant is still published in store 1, but editing variant 2 triggered reindexing it in all stores, and the variant is not visible in store 1 - so it was unpublished
        $this->assertVariantIsPublished(self::$keyOfVariant1InStore1);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore1);

        // - variant is still published in store 2, but since it was made invisible in store 2 - it's unpublished
        $this->assertVariantIsPublished(self::$keyOfVariant1InStore2);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore2);

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
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$keyOfParentInStore1);

        // - parent is unpublished from store 2
        $this->assertDataIsNotPublished(self::$keyOfParentInStore2);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertVariantIsNotPublished(self::$keyOfVariant1InStore1);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore1);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertVariantIsNotPublished(self::$keyOfVariant1InStore2);
        $this->assertVariantIsNotPublished(self::$keyOfVariant2InStore2);

        // revert changes
        $this->makeParentProductVisibleInStore2();
    }
}
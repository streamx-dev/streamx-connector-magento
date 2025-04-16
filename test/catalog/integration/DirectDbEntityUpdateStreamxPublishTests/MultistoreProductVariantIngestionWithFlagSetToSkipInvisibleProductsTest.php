<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesProductIndexer
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
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$store1Id);

        // - parent is still in store 2 but now contains only variant 1
        $this->assertParentIsPublishedWithoutVariant2InPayload(self::$store2Id);

        // - variant is still published in store 1, but editing variant 2 triggered reindexing it in all stores, and the variant is not visible in store 1 - so it was unpublished
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1]);

        // - variant is still published in store 2, but since it was disabled in store 2 - it's unpublished
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
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$store1Id);

        // - parent is unpublished from store 2
        $this->assertParentIsNotPublished(self::$store2Id);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertNoSeparatelyPublishedVariants(self::$store1Id);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertNoSeparatelyPublishedVariants(self::$store2Id);
    }

    /** @test */
    public function verifyIngestion_OnMakeVariantInvisible() {
        // given
        $this->makeProductInvisibleInStore2(self::$variant2);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$store1Id);

        // - parent is still in store 2 and contains both variants (invisible variants are always published in the parent's payload)
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$store2Id);

        // - variant is still published in store 1, but editing variant 2 triggered reindexing it in all stores, and the variant is not visible in store 1 - so it was unpublished
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1]);

        // - variant is still published in store 2, but since it was made invisible in store 2 - it's unpublished
        $this->assertSeparatelyPublishedVariants(self::$store2Id, [self::$variant1]);
    }

    /** @test */
    public function verifyIngestion_OnMakeParentInvisible() {
        // given
        $this->makeProductInvisibleInStore2(self::$parent);

        // when
        $this->reindexMview();

        // then
        // - parent is still in store 1 and contains both variants
        $this->assertParentIsPublishedWithAllVariantsInPayload(self::$store1Id);

        // - parent is unpublished from store 2
        $this->assertParentIsNotPublished(self::$store2Id);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertNoSeparatelyPublishedVariants(self::$store1Id);

        // - editing parent triggered re-publishing its variants, but since they are invisible - both should be unpublished
        $this->assertNoSeparatelyPublishedVariants(self::$store2Id);
    }
}
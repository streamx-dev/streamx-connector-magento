<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationKeyPaths;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateWhenChangedProductTypeSettingsTest extends BaseDirectDbEntityUpdateTest {

    private const PARENT_PRODUCT_NAME = 'Chaz Kangeroo Hoodie';
    private const CHILD_PRODUCT_NAME = 'Chaz Kangeroo Hoodie-XS-Black';

    // list of possible modes (no enums in PHP)
    private const EDIT_PARENT = 'MODE_1';
    private const EDIT_VARIANT = 'MODE_2';
    private const SHOULD_PUBLISH_PARENT = 'MODE_3';
    private const SHOULD_NOT_PUBLISH_PARENT = 'MODE_4';
    private const SHOULD_PUBLISH_VARIANT = 'MODE_5';
    private const SHOULD_NOT_PUBLISH_VARIANT = 'MODE_6';

    /** @test */
    public function verifyPublishing_WhenParentIsEdited_AndConfigurableProductsDisabledInConfig() {
        $this->verifyPublishedProducts_WhenProductIsEdited(
            'simple,grouped',
            self::EDIT_PARENT,
            self::SHOULD_NOT_PUBLISH_PARENT,
            self::SHOULD_PUBLISH_VARIANT
        );
    }

    /** @test */
    public function verifyPublishing_WhenVariantIsEdited_AndConfigurableProductsDisabledInConfig() {
        $this->verifyPublishedProducts_WhenProductIsEdited(
            'simple,grouped',
            self::EDIT_VARIANT,
            self::SHOULD_NOT_PUBLISH_PARENT,
            self::SHOULD_PUBLISH_VARIANT
        );
    }

    /** @test */
    public function verifyPublishing_WhenParentIsEdited_AndSimpleProductsDisabledInConfig() {
        $this->verifyPublishedProducts_WhenProductIsEdited(
            'configurable,grouped',
            self::EDIT_PARENT,
            self::SHOULD_PUBLISH_PARENT,
            self::SHOULD_NOT_PUBLISH_VARIANT
        );
    }

    /** @test */
    public function verifyPublishing_WhenVariantIsEdited_AndSimpleProductsDisabledInConfig() {
        $this->verifyPublishedProducts_WhenProductIsEdited(
            'configurable,grouped',
            self::EDIT_VARIANT,
            self::SHOULD_PUBLISH_PARENT,
            self::SHOULD_NOT_PUBLISH_VARIANT
        );
    }

    /** @test */
    public function verifyPublishing_WhenParentIsEdited_AndSimpleAndConfigurableProductsDisabledInConfig() {
        $this->verifyPublishedProducts_WhenProductIsEdited(
            'grouped',
            self::EDIT_PARENT,
            self::SHOULD_NOT_PUBLISH_PARENT,
            self::SHOULD_NOT_PUBLISH_VARIANT
        );
    }

    /** @test */
    public function verifyPublishing_WhenVariantIsEdited_AndSimpleAndConfigurableProductsDisabledInConfig() {
        $this->verifyPublishedProducts_WhenProductIsEdited(
            'grouped',
            self::EDIT_VARIANT,
            self::SHOULD_NOT_PUBLISH_PARENT,
            self::SHOULD_NOT_PUBLISH_VARIANT
        );
    }

    private function verifyPublishedProducts_WhenProductIsEdited(
        string $exportedProductTypes,
        string $productToEditMode,
        string $shouldPublishParentMode,
        string $shouldPublishVariantMode
    ) {
        // given
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        // variant must be visible to be published along with parent
        $childProductId = self::$db->getProductId(self::CHILD_PRODUCT_NAME);
        self::$db->setProductsVisibleInStore(self::$store1Id, $childProductId); // by default variants are not visible in store

        // remove from StreamX, if published before
        $parentProductKey = self::productKey($parentProductId);
        $childProductKey = self::productKey($childProductId);
        self::removeFromStreamX($parentProductKey, $childProductKey);

        // when
        ConfigurationEditUtils::setConfigurationValue(ConfigurationKeyPaths::ALLOWED_PRODUCT_TYPES, $exportedProductTypes);

        if ($productToEditMode == self::EDIT_PARENT) {
            self::$db->renameProduct($parentProductId, "Name modified for testing, was " . self::PARENT_PRODUCT_NAME);
        } else if ($productToEditMode == self::EDIT_VARIANT) {
            self::$db->renameProduct($childProductId, "Name modified for testing, was " . self::CHILD_PRODUCT_NAME);
        }

        try {
            // and
            $this->reindexMview();

            // then
            $regexReplacements = [
                'Name modified for testing, was ' => '',
                'name-modified-for-testing-was-' => ''
            ]; // allows comparing actual data to jsons with original products

            if ($shouldPublishParentMode == self::SHOULD_PUBLISH_PARENT) {
                $this->assertExactDataIsPublished($parentProductKey, 'original-hoodie-product.json', $regexReplacements);
            } else {
                $this->assertDataIsNotPublished($parentProductKey);
            }
            if ($shouldPublishVariantMode == self::SHOULD_PUBLISH_VARIANT) {
                $this->assertExactDataIsPublished($childProductKey, 'original-hoodie-xs-black-product.json', $regexReplacements);
            } else {
                $this->assertDataIsNotPublished($childProductKey);
            }
        } finally {
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationKeyPaths::ALLOWED_PRODUCT_TYPES);
            if ($productToEditMode == self::EDIT_PARENT) {
                self::$db->renameProduct($parentProductId, self::PARENT_PRODUCT_NAME);
            } else if ($productToEditMode == self::EDIT_VARIANT) {
                self::$db->renameProduct($childProductId, self::CHILD_PRODUCT_NAME);
            }
            self::$db->unsetProductsVisibleInStore(self::$store1Id, $childProductId); // restore default visibility of child product
        }
    }
}
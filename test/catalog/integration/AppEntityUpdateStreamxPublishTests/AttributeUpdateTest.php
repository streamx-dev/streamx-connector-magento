<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesAttributeIndexer
 */
class AttributeUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductThatUsesSimpleAttributeEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedUsingMagentoApplicationToStreamx(
            'sale',
            'edited-sale-attr-ball-product.json'
        );
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeWithOptionsEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedUsingMagentoApplicationToStreamx(
            'material',
            'edited-material-attr-ball-product.json'
        );
    }

    private function shouldPublishProductThatUsesAttributeEditedUsingMagentoApplicationToStreamx(string $attributeCode, string $validationFile): void {
        // given
        $attributeId = self::$db->getProductAttributeId($attributeCode);

        $oldDisplayName = self::$db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $productId = self::$db->getProductId('Dual Handle Cardio Ball'); // this product is known to have both "sale" and "material" attributes
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::setIndexedProductAttributes('sale', 'material');

        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, $validationFile);
        } finally {
            try {
                self::renameAttribute($attributeCode, $oldDisplayName);
                $this->assertExactDataIsPublished($expectedKey, "original-ball-product.json");
            } finally {
                ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
            }
        }
    }

    private function renameAttribute(string $attributeCode, string $newName): void {
        $coverage = MagentoEndpointsCaller::call('attribute/rename', [
            'attributeCode' => $attributeCode,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 */
class AttributeUpdateTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductThatUsesSimpleAttributeEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedUsingMagentoApplicationToStreamx(
            'color', // TODO this test takes long, since a lot of products use color attribute. Investigate main code to make it faster
            ['"label": "Color"' => '"label": "Name modified for testing, was Color"']
        );
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeWithOptionsEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedUsingMagentoApplicationToStreamx(
            'material',
            ['"label": "Material"' => '"label": "Name modified for testing, was Material"']
        );
    }

    private function shouldPublishProductThatUsesAttributeEditedUsingMagentoApplicationToStreamx(string $attributeCode, array $regexReplacementsForEditedValidationFile): void {
        // given
        $attributeId = $this->db->getProductAttributeId($attributeCode);

        $oldDisplayName = $this->db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $productId = $this->db->getProductId('Sprite Stasis Ball 55 cm'); // this product is known to have both "color" and "material" attributes
        $expectedKey = "pim:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->allowIndexingAllAttributes();
        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, "edited-ball-product.json", $regexReplacementsForEditedValidationFile);
        } finally {
            try {
                self::renameAttribute($attributeCode, $oldDisplayName);
                $this->assertExactDataIsPublished($expectedKey, "original-ball-product.json");
            } finally {
                self::restoreDefaultIndexingAttributes();
            }
        }
    }

    private function renameAttribute(string $attributeCode, string $newName): void {
        $coverage = $this->callMagentoPutEndpoint('attribute/rename', [
            'attributeCode' => $attributeCode,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
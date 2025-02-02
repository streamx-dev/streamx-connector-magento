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
    public function shouldPublishSimpleAttributeEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishAttributeEditedUsingMagentoApplicationToStreamx('description');
    }

    /** @test */
    public function shouldPublishAttributeWithOptionsEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishAttributeEditedUsingMagentoApplicationToStreamx('size');
    }

    private function shouldPublishAttributeEditedUsingMagentoApplicationToStreamx(string $attributeCode): void {
        // given
        $attributeId = $this->db->getProductAttributeId($attributeCode);

        $oldDisplayName = $this->db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $expectedKey = "attr:$attributeId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, "edited-$attributeCode-attribute.json");
        } finally {
            self::renameAttribute($attributeCode, $oldDisplayName);
            $this->assertExactDataIsPublished($expectedKey, "original-$attributeCode-attribute.json");
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
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
    public function shouldPublishAttributeEditedUsingMagentoApplicationToStreamx() {
        // given
        $attributeCode = 'description';
        $attributeId = $this->db->getProductAttributeId($attributeCode);

        $newDisplayName = 'Description attribute name modified for testing';
        $oldDisplayName = $this->db->getAttributeDisplayName($attributeId);

        // and
        $expectedKey = "attribute_$attributeId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, 'edited-description-attribute.json');
        } finally {
            self::renameAttribute($attributeCode, $oldDisplayName);
            $this->assertExactDataIsPublished($expectedKey, 'original-description-attribute.json');
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
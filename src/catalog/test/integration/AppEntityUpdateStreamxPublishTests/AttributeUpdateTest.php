<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use function date;

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

        $newDisplayName = 'Description attribute name modified for testing, at ' . date("Y-m-d H:i:s");
        $oldDisplayName = $this->db->getAttributeDisplayName($attributeId);

        // and
        $expectedKey = "attribute_$attributeId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        try {
            $this->assertDataIsPublished($expectedKey, $newDisplayName);
        } finally {
            self::renameAttribute($attributeCode, $oldDisplayName);
            $this->assertDataIsPublished($expectedKey, $oldDisplayName);
        }
    }

    private function renameAttribute(string $attributeCode, string $newName) {
        $this->callMagentoPutEndpoint('attribute/rename', [
            'attributeCode' => $attributeCode,
            'newName' => $newName
        ]);
    }
}
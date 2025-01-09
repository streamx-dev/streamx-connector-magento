<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishAttributeAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedAttribute() {
        // given
        $attributeCode = 'the_new_attribute';

        // when
        $attributeId = self::addAttribute($attributeCode);

        // then
        $expectedKey = "attribute_$attributeId";
        try {
            $this->assertDataIsPublished($expectedKey, $attributeCode);
        } finally {
            // and when
            self::deleteAttribute($attributeId);

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    private function addAttribute(string $attributeCode): int {
        return (int) $this->callMagentoPutEndpoint('attribute/add', [
            'attributeCode' => $attributeCode
        ]);
    }

    private function deleteAttribute(int $attributeId): void {
        $this->callMagentoPutEndpoint('attribute/delete', [
            'attributeId' => $attributeId
        ]);
    }
}
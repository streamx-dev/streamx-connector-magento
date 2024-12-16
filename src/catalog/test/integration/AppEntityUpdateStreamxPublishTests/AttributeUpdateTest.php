<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
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
        $attributeId = MagentoMySqlQueryExecutor::getProductAttributeId($attributeCode);

        $newDisplayName = 'Description attribute name modified for testing, at ' . date("Y-m-d H:i:s");
        $oldDisplayName = MagentoMySqlQueryExecutor::getAttributeDisplayName($attributeId);

        // when
        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        $expectedKey = "attribute_$attributeId";
        try {
            $this->assertDataIsPublished($expectedKey, $newDisplayName);
        } finally {
            self::renameAttribute($attributeCode, $oldDisplayName);
            $this->assertDataIsPublished($expectedKey, $oldDisplayName);
        }
    }

    private function renameAttribute(string $attributeCode, string $newName) {
        $this->callRestApiEndpoint('attribute/rename', [
            'attributeCode' => $attributeCode,
            'newName' => $newName
        ]);
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor as DB;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishAttributeAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedAttribute() {
        // given
        $attributeCode = 'the_new_attribute';

        // when
        $attributeId = self::insertNewAttribute($attributeCode);
        $this->reindexMview();

        // then
        $expectedKey = "attribute_$attributeId";
        try {
            $this->assertDataIsPublished($expectedKey, $attributeCode);
        } finally {
            // and when
            self::deleteAttribute($attributeId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /**
     * Inserts new attribute to database
     * @return int ID of the inserted attribute
     */
    private static function insertNewAttribute(string $attributeCode): int {
        $attributeName = "Display name of $attributeCode";
        $entityTypeId = DB::getProductEntityTypeId();

        DB::execute("
            INSERT INTO eav_attribute (entity_type_id, attribute_code, frontend_label, backend_type, frontend_input, is_user_defined) VALUES
                ($entityTypeId, '$attributeCode', '$attributeName', 'text', 'textarea', TRUE)
        ");

        $attributeId = DB::selectFirstField("
            SELECT MAX(attribute_id)
              FROM eav_attribute
        ");

        DB::execute("
            INSERT INTO catalog_eav_attribute (attribute_id, is_visible, is_visible_on_front, used_in_product_listing, is_visible_in_advanced_search)
                VALUES ($attributeId, TRUE, TRUE, TRUE, TRUE)
        ");

        return $attributeId;
    }

    private static function deleteAttribute(int $attributeId): void {
        DB::executeAll([
            "DELETE FROM catalog_eav_attribute WHERE attribute_id = $attributeId",
            "DELETE FROM eav_attribute WHERE attribute_id = $attributeId"
        ]);
    }

    private static function attrId($attrCode): string {
        return DB::getAttributeAttributeId($attrCode);
    }
}
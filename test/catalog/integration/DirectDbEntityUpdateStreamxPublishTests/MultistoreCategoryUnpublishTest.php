<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class MultistoreCategoryUnpublishTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldUnpublishCategoryFromStore2_WhenCategorySwitchedToNotActiveInStore2() {
        // given
        $category = self::$db->getCategoryId('Gear');
        $validationFile = 'original-gear-category.json';
        $isActiveAttributeId = self::$db->getCategoryAttributeId('is_active');

        // and: prepare expected keys
        $keyForStore1 = self::categoryKey($category, self::DEFAULT_STORE_CODE);
        $keyForStore2 = self::categoryKey($category, self::STORE_2_CODE);
        $this->removeFromStreamX($keyForStore1, $keyForStore2);

        try {
            // when 1: perform any change in the category, to trigger publishing it from both stores
            self::$db->execute("UPDATE catalog_category_entity SET children_count = children_count + 1 WHERE entity_id = {$category->getEntityId()}");
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($keyForStore1, $validationFile);
            $this->assertExactDataIsPublished($keyForStore2, $validationFile);

            // when 2: disable the category for store 2
            self::$db->insertIntCategoryAttribute($category, $isActiveAttributeId, self::$store2Id, 0);
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($keyForStore1, $validationFile);
            $this->assertDataIsUnpublished($keyForStore2);

            // when 3: restore the category as active for store 2
            self::$db->deleteIntCategoryAttribute($category, $isActiveAttributeId, self::$store2Id);
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($keyForStore1, $validationFile);
            $this->assertExactDataIsPublished($keyForStore2, $validationFile);
        } finally {
            // restore DB changes performed by the test, in case of any assertion failed
            self::$db->execute("UPDATE catalog_category_entity SET children_count = children_count - 1 WHERE entity_id = {$category->getEntityId()}");
            self::$db->deleteIntCategoryAttribute($category, $isActiveAttributeId, self::$store2Id);
        }
    }
}
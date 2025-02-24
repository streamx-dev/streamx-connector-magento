<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\SlugGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductWithVariantsEditedDirectlyInDatabaseToStreamx() {
        // given
        $nameOfProductToEdit = 'Chaz Kangeroo Hoodie';
        $idOfProductToEdit = self::$db->getProductId($nameOfProductToEdit);
        $newNameOfProductToEdit = "Name modified for testing, was $nameOfProductToEdit";

        // and
        $expectedPublishedKey = "pim:$idOfProductToEdit";
        $unexpectedPublishedKey = 'pim:' . self::$db->getProductId('Chaz Kangeroo Hoodie-XL-Orange');

        self::removeFromStreamX($expectedPublishedKey, $unexpectedPublishedKey);

        // when
        self::$db->renameProduct($idOfProductToEdit, $newNameOfProductToEdit);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedPublishedKey, 'edited-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        } finally {
            self::$db->renameProduct($idOfProductToEdit, $nameOfProductToEdit);
        }
    }

    /** @test */
    public function shouldPublishParentOfProductVariantEditedUsingDirectlyInDatabaseToStreamx() {
        // given
        $nameOfProductToEdit = 'Chaz Kangeroo Hoodie-XL-Orange';
        $idOfProductToEdit = self::$db->getProductId($nameOfProductToEdit);
        $newNameOfProductToEdit = "Name modified for testing, was $nameOfProductToEdit";

        // and
        $expectedPublishedKey = 'pim:' . self::$db->getProductId('Chaz Kangeroo Hoodie');
        $unexpectedPublishedKey = "pim:$idOfProductToEdit";

        self::removeFromStreamX($expectedPublishedKey, $unexpectedPublishedKey);

        // when
        self::$db->renameProduct($idOfProductToEdit, $newNameOfProductToEdit);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedPublishedKey, 'original-hoodie-product.json', [
                '"' . $nameOfProductToEdit => '"' . $newNameOfProductToEdit,
                '"' . SlugGenerator::slugify($nameOfProductToEdit) => '"' . SlugGenerator::slugify($newNameOfProductToEdit)
            ]);
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        } finally {
            self::$db->renameProduct($idOfProductToEdit, $nameOfProductToEdit);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\SlugGenerator;

/**
 * @inheritdoc
 */
class ProductVariantUpdateTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductWithVariantsEditedDirectlyInDatabaseToStreamx() {
        // given
        $nameOfProductToEdit = 'Chaz Kangeroo Hoodie';
        $idOfProductToEdit = $this->db->getProductId($nameOfProductToEdit);
        $newNameOfProductToEdit = "Name modified for testing, was $nameOfProductToEdit";

        // and
        $expectedPublishedKey = "pim:$idOfProductToEdit";
        $unexpectedPublishedKey = 'pim:' . $this->db->getProductId('Chaz Kangeroo Hoodie-XL-Orange');

        self::removeFromStreamX($expectedPublishedKey, $unexpectedPublishedKey);

        // when
        $this->db->renameProduct($idOfProductToEdit, $newNameOfProductToEdit);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedPublishedKey, 'edited-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        } finally {
            $this->db->renameProduct($idOfProductToEdit, $nameOfProductToEdit);
        }
    }

    /** @test */
    public function shouldPublishParentOfProductVariantEditedUsingDirectlyInDatabaseToStreamx() {
        // TODO check what causes both edited variant and parent be sent? Maybe due to change events?
        // given
        $nameOfProductToEdit = 'Chaz Kangeroo Hoodie-XL-Orange';
        $idOfProductToEdit = $this->db->getProductId($nameOfProductToEdit);
        $newNameOfProductToEdit = "Name modified for testing, was $nameOfProductToEdit";

        // and
        $expectedPublishedKey = 'pim:' . $this->db->getProductId('Chaz Kangeroo Hoodie');
        $unexpectedPublishedKey = "pim:$idOfProductToEdit";

        self::removeFromStreamX($expectedPublishedKey, $unexpectedPublishedKey);

        // when
        $this->db->renameProduct($idOfProductToEdit, $newNameOfProductToEdit);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedPublishedKey, 'original-hoodie-product.json', [
                '"' . $nameOfProductToEdit => '"' . $newNameOfProductToEdit,
                '"' . SlugGenerator::slugify($nameOfProductToEdit) => '"' . SlugGenerator::slugify($newNameOfProductToEdit)
            ]);
            // TODO: the variant product should not be published separately
            $this->assertDataIsPublished($unexpectedPublishedKey, $newNameOfProductToEdit);
        } finally {
            $this->db->renameProduct($idOfProductToEdit, $nameOfProductToEdit);
        }
    }
}
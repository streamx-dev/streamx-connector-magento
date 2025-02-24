<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductWithVariantsEditedUsingMagentoApplicationToStreamx() {
        // given
        $nameOfProductToEdit = 'Chaz Kangeroo Hoodie';
        $idOfProductToEdit = self::$db->getProductId($nameOfProductToEdit);
        $newNameOfProductToEdit = "Name modified for testing, was $nameOfProductToEdit";

        // and
        $expectedPublishedKey = "pim:$idOfProductToEdit";
        $unexpectedPublishedKey = 'pim:' . self::$db->getProductId('Chaz Kangeroo Hoodie-XL-Orange');

        self::removeFromStreamX($expectedPublishedKey, $unexpectedPublishedKey);

        // when
        self::renameProduct($idOfProductToEdit, $newNameOfProductToEdit);

        // then
        try {
            $this->assertExactDataIsPublished($expectedPublishedKey, 'edited-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        } finally {
            self::renameProduct($idOfProductToEdit, $nameOfProductToEdit);
            $this->assertExactDataIsPublished($expectedPublishedKey, 'original-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        }
    }

    /** @test */
    public function shouldPublishParentOfProductVariantEditedUsingMagentoApplicationToStreamx() {
        // given
        $nameOfProductToEdit = 'Chaz Kangeroo Hoodie-XL-Orange';
        $idOfProductToEdit = self::$db->getProductId($nameOfProductToEdit);
        $newNameOfProductToEdit = "Name modified for testing, was $nameOfProductToEdit";

        // and
        $expectedPublishedKey = 'pim:' . self::$db->getProductId('Chaz Kangeroo Hoodie');
        $unexpectedPublishedKey = "pim:$idOfProductToEdit";

        self::removeFromStreamX($expectedPublishedKey, $unexpectedPublishedKey);

        // when
        self::renameProduct($idOfProductToEdit, $newNameOfProductToEdit);

        // then
        try {
            $this->assertExactDataIsPublished($expectedPublishedKey, 'original-hoodie-product.json', [
                '"' . $nameOfProductToEdit => '"' . $newNameOfProductToEdit,
                '"' . SlugGenerator::slugify($nameOfProductToEdit) => '"' . SlugGenerator::slugify($newNameOfProductToEdit)
            ]);
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        } finally {
            self::renameProduct($idOfProductToEdit, $nameOfProductToEdit);
            $this->assertExactDataIsPublished($expectedPublishedKey, 'original-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedPublishedKey);
        }
    }

    private function renameProduct(int $productId, string $newName): void {
        $coverage = self::callMagentoPutEndpoint('product/rename', [
            'productId' => $productId,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseAppEntityUpdateTest {

    private const PARENT_PRODUCT_NAME = 'Chaz Kangeroo Hoodie';
    private const CHILD_PRODUCT_NAME = 'Chaz Kangeroo Hoodie-XL-Orange';

    /** @test */
    public function shouldPublishParentProductAndVisibleVariants_WhenParentIsEditedUsingMagentoApplication() {
        // given
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        $childProducts = self::$db->getProductIdsAndNamesList(self::PARENT_PRODUCT_NAME . '-');
        $this->assertCount(15, $childProducts);

        // and: make some of the child products visible
        foreach ($childProducts as $childProduct) {
            $entityIds = $childProduct->getEntityIds();
            if ($entityIds->getLinkFieldId() %2 == 0) {
                $visibleChildProducts[] = $childProduct;
            } else {
                $invisibleChildProducts[] = $childProduct;
            }
        }
        $visibleChildProductIds = array_map(function ($product) {
            return $product->getEntityIds();
        }, $visibleChildProducts);
        self::$db->setProductsVisibleInStore(self::$store1Id, ...$visibleChildProductIds);

        // and
        $expectedParentProductKey = self::productKey($parentProductId);
        $expectedChildProductsKeys = array_map(function ($childProduct) {
            return self::productKey($childProduct->getEntityIds());
        }, $childProducts);

        self::removeFromStreamX($expectedParentProductKey, ...$expectedChildProductsKeys);

        // when
        self::renameProduct($parentProductId, "Name modified for testing, was " . self::PARENT_PRODUCT_NAME);

        // then
        try {
            $this->assertExactDataIsPublished($expectedParentProductKey, 'edited-hoodie-product.json');
            foreach ($visibleChildProducts as $childProduct) {
                $entityIds = $childProduct->getEntityIds();
                $publishedChildProduct = $this->downloadContentAtKey(self::productKey($entityIds));
                $this->assertStringContainsString('"id":' . $entityIds->getEntityId(), $publishedChildProduct);
                $this->assertStringContainsString('"name":"' . $childProduct->getName() . '"', $publishedChildProduct);
            }
            foreach ($invisibleChildProducts as $childProduct) {
                $expectedNotPublishedKey = self::productKey($childProduct->getEntityIds());
                $this->assertDataIsNotPublished($expectedNotPublishedKey);
            }
        } finally {
            self::renameProduct($parentProductId, self::PARENT_PRODUCT_NAME);
            try {
                $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json');
            } finally {
                // restore default visibility of child products
                self::$db->unsetProductsVisibleInStore(self::$store1Id, ...$visibleChildProductIds);
            }
        }
    }

    /** @test */
    public function shouldPublishVisibleVariantAndParentProduct_WhenVariantIsEditedUsingMagentoApplication() {
        $childProductId = self::$db->getProductId(self::CHILD_PRODUCT_NAME);
        try {
            // make the variant visible at store level, so it can be published
            self::$db->setProductsVisibleInStore(self::$store1Id, $childProductId);
            $this->testPublishingWhenVariantIsEdited(true);
        } finally {
            // restore no visibility for variant
            self::$db->unsetProductsVisibleInStore(self::$store1Id, $childProductId);
        }
    }

    /** @test */
    public function shouldNotPublishInvisibleVariant_ButPublishParentProduct_WhenVariantIsEditedUsingMagentoApplication() {
        $this->testPublishingWhenVariantIsEdited(false);
    }

    private function testPublishingWhenVariantIsEdited(bool $expectingVariantToBePublished) {
        // given
        $childProductId = self::$db->getProductId(self::CHILD_PRODUCT_NAME);
        $differentChildId = self::$db->getProductId('Chaz Kangeroo Hoodie-L-Orange');
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        // and
        $expectedChildProductKey = self::productKey($childProductId);
        $expectedParentProductKey = self::productKey($parentProductId);
        $unexpectedChildProductKey = self::productKey($differentChildId);

        self::removeFromStreamX($expectedChildProductKey, $expectedParentProductKey, $unexpectedChildProductKey);

        // when
        $childProductModifiedName = "Name modified for testing, was " . self::CHILD_PRODUCT_NAME;
        self::renameProduct($childProductId, $childProductModifiedName);

        // then: expecting both products to be published (with modified name of the child product in both payloads). Other child should not be published
        // note: $expectingVariantToBePublished is passed as a flag, because publishing a variant depends on its visibility setting
        try {
            if ($expectingVariantToBePublished) {
                $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json', [
                    '"' . $childProductModifiedName => '"' . self::CHILD_PRODUCT_NAME,
                    '"' . SlugGenerator::slugify($childProductModifiedName) => '"' . SlugGenerator::slugify(self::CHILD_PRODUCT_NAME)
                ]);
            } else {
                $this->assertDataIsNotPublished($expectedChildProductKey);
            }
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json', [
                '"' . $childProductModifiedName => '"' . self::CHILD_PRODUCT_NAME,
                '"' . SlugGenerator::slugify($childProductModifiedName) => '"' . SlugGenerator::slugify(self::CHILD_PRODUCT_NAME)
            ]);
            $this->assertDataIsNotPublished($unexpectedChildProductKey);
        } finally {
            // when: restore product name
            self::renameProduct($childProductId, self::CHILD_PRODUCT_NAME);

            // then: expecting both products to be published (with original name of the child product in both payloads)
            if ($expectingVariantToBePublished) {
                $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json');
            } else {
                $this->assertDataIsNotPublished($expectedChildProductKey);
            }
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedChildProductKey);
        }
    }

    private function renameProduct(EntityIds $productId, string $newName): void {
        MagentoEndpointsCaller::call('product/rename', [
            'productId' => $productId->getEntityId(),
            'newName' => $newName
        ]);
    }
}
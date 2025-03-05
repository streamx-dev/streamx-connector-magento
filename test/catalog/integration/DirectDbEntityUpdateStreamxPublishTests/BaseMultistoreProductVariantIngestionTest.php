<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

/**
 * @inheritdoc
 */
abstract class BaseMultistoreProductVariantIngestionTest extends BaseDirectDbEntityUpdateTest {

    private const PARENT_ID = 62;
    private const VARIANT_1_ID = 60;
    private const VARIANT_2_ID = 61;

    private const PARENT_NAME = 'Chaz Kangeroo Hoodie';
    private const VARIANT_1_NAME = 'Chaz Kangeroo Hoodie-XL-Gray';
    private const VARIANT_2_NAME = 'Chaz Kangeroo Hoodie-XL-Orange';

    private const PARENT_JSON_FILE = 'original-hoodie-product.json';
    private const VARIANT_1_JSON_FILE = 'original-hoodie-xl-gray-product.json';
    private const VARIANT_2_JSON_FILE = 'original-hoodie-xl-orange-product.json';

    protected static EntityIds $parent;
    protected static EntityIds $variant1;
    protected static EntityIds $variant2;

    private static string $keyOfParentInStore1;
    private static string $keyOfVariant1InStore1;
    private static string $keyOfVariant2InStore1;

    private static string $keyOfParentInStore2;
    private static string $keyOfVariant1InStore2;
    private static string $keyOfVariant2InStore2;

    private static int $statusAttributeId;
    private static int $visibilityAttributeId;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        self::$parent = self::$db->getProductId(self::PARENT_NAME);
        self::$variant1 = self::$db->getProductId(self::VARIANT_1_NAME);
        self::$variant2 = self::$db->getProductId(self::VARIANT_2_NAME);

        self::$keyOfParentInStore1 = self::productKey(self::$parent, self::DEFAULT_STORE_CODE);
        self::$keyOfVariant1InStore1 = self::productKey(self::$variant1, self::DEFAULT_STORE_CODE);
        self::$keyOfVariant2InStore1 = self::productKey(self::$variant2, self::DEFAULT_STORE_CODE);

        self::$keyOfParentInStore2 = self::productKey(self::$parent, self::STORE_2_CODE);
        self::$keyOfVariant1InStore2 = self::productKey(self::$variant1, self::STORE_2_CODE);
        self::$keyOfVariant2InStore2 = self::productKey(self::$variant2, self::STORE_2_CODE);

        self::$visibilityAttributeId = self::$db->getProductAttributeId('visibility');
        self::$statusAttributeId = self::$db->getProductAttributeId('status');
    }

    protected function setUp(): void {
        // prepare initial state for every test in this class: make all tested products published (so every test can verify if some are unpublished)
        $streamxClientForStore1 = parent::createStreamxClient(self::$store1Id, self::DEFAULT_STORE_CODE);
        $streamxClientForStore2 = parent::createStreamxClient(self::$store2Id, self::STORE_2_CODE);

        $publishProductsPayload = [
            self::PARENT_ID => $this->readJsonFileToArray(self::PARENT_JSON_FILE),
            self::VARIANT_1_ID => $this->readJsonFileToArray(self::VARIANT_1_JSON_FILE),
            self::VARIANT_2_ID => $this->readJsonFileToArray(self::VARIANT_2_JSON_FILE)
        ];

        $streamxClientForStore1->publish($publishProductsPayload, ProductProcessor::INDEXER_ID);
        $streamxClientForStore2->publish($publishProductsPayload, ProductProcessor::INDEXER_ID);

        // verify all three products are published from both stores
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store1Id, [self::$variant1, self::$variant2]);
        $this->assertParentIsPublishedWithVariantsInPayload(self::$store2Id, [self::$variant1, self::$variant2]);
        $this->assertSeparatelyPublishedVariants(self::$store1Id, [self::$variant1, self::$variant2]);
        $this->assertSeparatelyPublishedVariants(self::$store2Id, [self::$variant1, self::$variant2]);

        parent::setUp();
    }

    private function readJsonFileToArray(string $path): array {
        return json_decode($this->readValidationFileContent($path), true);
    }

    protected function tearDown(): void {
        parent::tearDown();

        // restore initial state of DB
        foreach ([self::$parent, self::$variant1, self::$variant2] as $product) {
            self::$db->deleteIntProductAttribute($product, self::$statusAttributeId, self::$store2Id);
            self::$db->deleteIntProductAttribute($product, self::$visibilityAttributeId, self::$store2Id);
        }

        // flush all changes
        $this->reindexMview();

        // clean up state on StreamX
        self::removeFromStreamX(
            self::$keyOfParentInStore1, self::$keyOfVariant1InStore1, self::$keyOfVariant2InStore1,
            self::$keyOfParentInStore2, self::$keyOfVariant1InStore2, self::$keyOfVariant2InStore2
        );
    }

    protected function disableProductInStore2(EntityIds $product): void {
        self::$db->insertIntProductAttribute($product, self::$statusAttributeId, self::$store2Id, Status::STATUS_DISABLED);
    }

    protected function makeProductInvisibleInStore2(EntityIds $product): void {
        self::$db->insertIntProductAttribute($product, self::$visibilityAttributeId, self::$store2Id, Visibility::VISIBILITY_NOT_VISIBLE);
    }

    protected function assertParentIsPublishedWithVariantsInPayload(int $storeId, array $expectedVariantsInPayload): void {
        if ($storeId == self::$store1Id) {
            $publishedParentProduct = $this->downloadContentAtKey(self::$keyOfParentInStore1);
        } else if ($storeId == self::$store2Id) {
            $publishedParentProduct = $this->downloadContentAtKey(self::$keyOfParentInStore2);
        }

        $expectingVariant1InParentPayload = in_array(self::$variant1, $expectedVariantsInPayload);
        $this->verifyJsonContainsNameOrNot($publishedParentProduct, self::VARIANT_1_NAME, $expectingVariant1InParentPayload);

        $expectingVariant2InParentPayload = in_array(self::$variant2, $expectedVariantsInPayload);
        $this->verifyJsonContainsNameOrNot($publishedParentProduct, self::VARIANT_2_NAME, $expectingVariant2InParentPayload);
    }

    protected function assertParentIsNotPublished(int $storeId): void {
        if ($storeId == self::$store1Id) {
            $productKey = self::$keyOfParentInStore1;
        } else if ($storeId == self::$store2Id) {
            $productKey = self::$keyOfParentInStore2;
        }

        $this->assertDataIsNotPublished($productKey);
    }

    protected function assertSeparatelyPublishedVariants(int $storeId, array $expectedPublishedVariants): void {
        $expectingVariant1ToBePublished = in_array(self::$variant1, $expectedPublishedVariants);
        $expectingVariant2ToBePublished = in_array(self::$variant2, $expectedPublishedVariants);

        if ($storeId == self::STORE_1_ID) {
            $variant1Key = self::$keyOfVariant1InStore1;
            $variant2Key = self::$keyOfVariant2InStore1;
        } else if ($storeId == self::$store2Id) {
            $variant1Key = self::$keyOfVariant1InStore2;
            $variant2Key = self::$keyOfVariant2InStore2;
        }

        $this->verifyPublishedWithNameInJsonOrNotPublished($variant1Key, self::VARIANT_1_NAME, $expectingVariant1ToBePublished);
        $this->verifyPublishedWithNameInJsonOrNotPublished($variant2Key, self::VARIANT_2_NAME, $expectingVariant2ToBePublished);
    }

    protected function assertNoSeparatelyPublishedVariants(int $storeId): void {
        $this->assertSeparatelyPublishedVariants($storeId, []);
    }

    private function verifyJsonContainsNameOrNot(string $json, string $productName, bool $shouldContain): void {
        $pattern = '/"name": ?"[^"]*"/';
        preg_match_all($pattern, $json, $matches);
        $names = $matches[0]; // array of names

        $tokenToSearch = '"name":"' . $productName . '"';

        if ($shouldContain) {
            $this->assertContains($tokenToSearch, $names);
        } else {
            $this->assertNotContains($tokenToSearch, $names);
        }
    }

    private function verifyPublishedWithNameInJsonOrNotPublished(string $key, string $productName, bool $shouldBePublished): void {
        if ($shouldBePublished) {
            $json = $this->downloadContentAtKey($key);
            $this->verifyJsonContainsNameOrNot($json, $productName, true);
        } else {
            $this->assertDataIsNotPublished($key);
        }
    }
}
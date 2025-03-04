<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductVariantPublishAndUnpublishTest extends BaseDirectDbEntityUpdateTest {

    /**
     * Unpublish configurable products scenarios:
     * 1. flag is set to: skip invisible products
     *   a) disable variant in store 2
     *      -> the variant should be unpublished / trigger publishing parent without that variant
     *   b) disable parent in store
     *      -> the parent should be unpublished / trigger publishing visible variants
     *   c) make variant invisible in store 2
     *      -> the variant should be unpublished / trigger publishing parent with that variant
     *   d) make parent invisible in store 2
     *      -> the parent should be unpublished / trigger publishing visible variants
     * 2. flag is set to: publish invisible products
     *   a) disable variant in store 2
     *      -> the variant should be unpublished / trigger publishing parent without that variant
     *   b) disable parent in store
     *      -> the parent should be unpublished / trigger publishing all variants
     *   c) make variant invisible in store 2
     *      -> the variant should be published / trigger publishing parent with that variant
     *   d) make parent invisible in store 2
     *      -> the parent should be published / trigger publishing all variants
     */

    private static EntityIds $parent;
    private static EntityIds $variant1;
    private static EntityIds $variant2;

    private static string $parentName = 'Chaz Kangeroo Hoodie'; // ID 62
    private static string $variant1Name = 'Chaz Kangeroo Hoodie-XL-Gray'; // ID 60
    private static string $variant2Name = 'Chaz Kangeroo Hoodie-XL-Orange'; // ID 61

    private static string $parentDefaultJsonFile = 'original-hoodie-product.json';
    private static string $variant1DefaultJsonFile = 'original-hoodie-xl-gray-product.json';
    private static string $variant2DefaultJsonFile = 'original-hoodie-xl-orange-product.json';

    private static string $keyOfParentInStore1;
    private static string $keyOfVariant1InStore1;
    private static string $keyOfVariant2InStore1;

    private static string $keyOfParentInStore2;
    private static string $keyOfVariant1InStore2;
    private static string $keyOfVariant2InStore2;

    private static int $statusAttributeId;
    private static int $visibilityAttributeId;

    private StreamxClient $streamxClientForStore1;
    private StreamxClient $streamxClientForStore2;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        self::$parent = self::$db->getProductId(self::$parentName);
        self::$variant1 = self::$db->getProductId(self::$variant1Name);
        self::$variant2 = self::$db->getProductId(self::$variant2Name);

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
        // as initial state for every test - make all tested products published (so every test can verify unpublishing)
        $this->streamxClientForStore1 = parent::createStreamxClient(self::STORE_1_ID, self::DEFAULT_STORE_CODE);
        $this->streamxClientForStore2 = parent::createStreamxClient(self::$store2Id, self::STORE_2_CODE);

        $parentDefaultJson = $this->readJsonFileToArray(self::$parentDefaultJsonFile);
        $variant1DefaultJson = $this->readJsonFileToArray(self::$variant1DefaultJsonFile);
        $variant2DefaultJson = $this->readJsonFileToArray(self::$variant2DefaultJsonFile);

        $this->streamxClientForStore1->publish([
            62 => $parentDefaultJson,
            60 => $variant1DefaultJson,
            61 => $variant2DefaultJson
        ], ProductProcessor::INDEXER_ID);

        $this->streamxClientForStore2->publish([
            62 => $parentDefaultJson,
            60 => $variant1DefaultJson,
            61 => $variant2DefaultJson
        ], ProductProcessor::INDEXER_ID);

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
            self::$db->execute("UPDATE catalog_product_entity SET attribute_set_id = 10 WHERE entity_id = {$product->getEntityId()}");
        }

        // flush all changes
        $this->reindexMview();

        // clean up state on StreamX
        self::removeFromStreamX(
            self::$keyOfParentInStore1, self::$keyOfVariant1InStore1, self::$keyOfVariant2InStore1,
            self::$keyOfParentInStore2, self::$keyOfVariant1InStore2, self::$keyOfVariant2InStore2
        );
    }

    private function dummyEditOfProduct(EntityIds $product): void {
        self::$db->execute("UPDATE catalog_product_entity SET attribute_set_id = attribute_set_id + 1 WHERE entity_id = {$product->getEntityId()}");
    }

    /** @test */
    public function verifyIngestion_OnDisableVariant_WhenFlagSetToSkipInvisibleProducts() {
        // given: entry state is: all products are published.
        self::$db->insertIntProductAttribute(self::$variant2, self::$statusAttributeId, self::$store2Id, Status::STATUS_DISABLED);

        // when
        $this->reindexMview();

        // then: the variant should be unpublished, and trigger publishing parent without that variant in its variants list
        $this->verifyPublishedProducts(
            true, // parent is still in store 1
            true, // and contains both variants
            true,
            true, // variant 1 is still in store 1
            false, // editing variant 2 triggered reindexing it in all stores, and it's not visible in store 1. So - unpublished

            true, // parent is still in store 2
            true, // but now contains only variant 1
            false,
            true, // variant 1 is still in store 2
            false // variant 2 was disabled in store 2
        );
    }

    private function verifyPublishedProducts(
        bool $expectingParentToBePublishedInStore1,
        bool $expectingVariant1ToBePresentInPayloadOfParentInStore1,
        bool $expectingVariant2ToBePresentInPayloadOfParentInStore1,
        bool $expectingVariant1ToBePublishedInStore1,
        bool $expectingVariant2ToBePublishedInStore1,

        bool $expectingParentToBePublishedInStore2,
        bool $expectingVariant1ToBePresentInPayloadOfParentInStore2,
        bool $expectingVariant2ToBePresentInPayloadOfParentInStore2,
        bool $expectingVariant1ToBePublishedInStore2,
        bool $expectingVariant2ToBePublishedInStore2
    ): void {
        if ($expectingParentToBePublishedInStore1) {
            $publishedParentProduct = $this->downloadContentAtKey(self::$keyOfParentInStore1);
            $this->verifyJsonContainsNameOrNot($publishedParentProduct, self::$variant1Name, $expectingVariant1ToBePresentInPayloadOfParentInStore1);
            $this->verifyJsonContainsNameOrNot($publishedParentProduct, self::$variant2Name, $expectingVariant2ToBePresentInPayloadOfParentInStore1);
        } else {
            $this->assertDataIsNotPublished(self::$keyOfParentInStore1);
        }
        $this->verifyPublishedWithNameInJsonOrNotPublished(self::$keyOfVariant1InStore1, self::$variant1Name, $expectingVariant1ToBePublishedInStore1);
        $this->verifyPublishedWithNameInJsonOrNotPublished(self::$keyOfVariant2InStore1, self::$variant2Name, $expectingVariant2ToBePublishedInStore1);

        if ($expectingParentToBePublishedInStore2) {
            $publishedParentProduct = $this->downloadContentAtKey(self::$keyOfParentInStore2);
            $this->verifyJsonContainsNameOrNot($publishedParentProduct, self::$variant1Name, $expectingVariant1ToBePresentInPayloadOfParentInStore2);
            $this->verifyJsonContainsNameOrNot($publishedParentProduct, self::$variant2Name, $expectingVariant2ToBePresentInPayloadOfParentInStore2);
        } else {
            $this->assertDataIsNotPublished(self::$keyOfParentInStore2);
        }
        $this->verifyPublishedWithNameInJsonOrNotPublished(self::$keyOfVariant1InStore2, self::$variant1Name, $expectingVariant1ToBePublishedInStore2);
        $this->verifyPublishedWithNameInJsonOrNotPublished(self::$keyOfVariant2InStore2, self::$variant2Name, $expectingVariant2ToBePublishedInStore2);
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
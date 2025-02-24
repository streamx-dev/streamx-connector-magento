<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * {@inheritdoc}
 *
 * Note: see StoresControllerImpl for additional stores and website created for these tests
 */
abstract class BaseMultistoreTest extends BaseDirectDbEntityUpdateTest {

    protected const DEFAULT_WEBSITE_ID = 1;
    protected const DEFAULT_STORE_ID = 0;
    protected const STORE_1_ID = 1;

    private static int $store2Id;
    private static int $secondWebsiteId;
    private static int $secondWebsiteStoreId;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        $wasDataCreated = self::callMagentoPutEndpoint('stores/setup', []);
        if ($wasDataCreated) {
            self::$indexerOperations->flushCache();
        }

        self::$store2Id = self::selectByCode('store_id', 'store', 'store_2_view');
        self::$secondWebsiteId = self::selectByCode('website_id', 'store_website', 'second_website');
        self::$secondWebsiteStoreId = self::selectByCode('store_id', 'store', 'store_for_second_website_view');
    }

    private static function selectByCode(string $idField, string $table, string $code): int {
        return intval(
            self::$db->selectSingleValue("SELECT $idField FROM $table WHERE code = '$code'")
        );
    }

    protected static function getStore2Id(): int {
        return self::$store2Id;
    }

    protected static function getSecondWebsiteId(): int {
        return self::$secondWebsiteId;
    }

    protected static function getSecondWebsiteStoreId(): int {
        return self::$secondWebsiteStoreId;
    }
}
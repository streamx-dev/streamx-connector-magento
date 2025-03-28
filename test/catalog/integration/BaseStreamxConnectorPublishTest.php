<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Exception;
use ReflectionClass;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests\BaseAppEntityUpdateTest;
use StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests\BaseDirectDbEntityUpdateTest;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoLogFileUtils;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;

/**
 * @inheritDoc
 *  Note: see StoresControllerImpl for additional stores and website created for these tests
 */
abstract class BaseStreamxConnectorPublishTest extends BaseStreamxTest {

    public const DEFAULT_STORE_ID = 0;

    protected static int $website1Id = 1;
    protected static int $store1Id = 1;
    protected static int $store2Id;
    protected static int $website2Id;
    protected static int $website2StoreId;

    protected const STORE_1_CODE = 'default';
    protected const STORE_2_CODE = 'store_2';
    protected const WEBSITE_2_STORE_CODE = 'store_for_second_website';

    protected static bool $areTestsInitialized = false;

    protected static MagentoMySqlQueryExecutor $db;

    protected static MagentoIndexerOperationsExecutor $indexerOperations;
    private static string $originalIndexerMode;
    private static bool $indexModeNeedsRestoring;

    protected static string $testedIndexerName;
    private static string $testedIndexerMode;

    private MagentoLogFileUtils $logFileUtils;

    public static function setUpBeforeClass(): void {
        if (!self::$areTestsInitialized) {
            self::initializeTests();
            self::$areTestsInitialized = true;
        }
        self::loadDesiredIndexerSettings();
        self::setIndexerModeInMagento();
    }

    public static function initializeTests(): void {
        self::$db = new MagentoMySqlQueryExecutor();
        self::$indexerOperations = new MagentoIndexerOperationsExecutor();

        if ("true" === MagentoEndpointsCaller::call('stores/setup')) {
            self::$indexerOperations->flushCache();
        }

        self::$store2Id = self::$db->selectSingleValue("SELECT store_id FROM store WHERE code = '" . self::STORE_2_CODE . "'");
        self::$website2Id = self::$db->selectSingleValue("SELECT website_id FROM store_website WHERE code = 'second_website'");
        self::$website2StoreId = self::$db->selectSingleValue("SELECT store_id FROM store WHERE code = '" . self::WEBSITE_2_STORE_CODE . "'");

        if (self::$db->isEnterpriseMagento()) {
            self::disableGiftCardsCategory();
        }
    }

    private static function disableGiftCardsCategory(): void {
        // disable a category that exists only in enterprise magento, to allow having common validation files for both versions
        $categoryId = self::$db->getCategoryId('Gift Cards');
        $isActiveAttributeId = self::$db->getCategoryAttributeId('is_active');
        self::$db->insertIntCategoryAttribute($categoryId, $isActiveAttributeId, 0);
    }

    public static function tearDownAfterClass(): void {
        self::restoreIndexerModeInMagento();
    }

    private static function setIndexerModeInMagento(): void {
        self::$indexerOperations->setIndexerName(self::$testedIndexerName);
        self::$originalIndexerMode = self::$indexerOperations->getIndexerMode();

        if (self::$testedIndexerMode !== self::$originalIndexerMode) {
            self::$indexerOperations->setIndexerMode(self::$testedIndexerMode);
            self::$indexModeNeedsRestoring = true;
        } else {
            self::$indexModeNeedsRestoring = false;
        }
    }

    private static function restoreIndexerModeInMagento(): void {
        if (self::$indexModeNeedsRestoring) {
            self::$indexerOperations->setIndexerMode(self::$originalIndexerMode);
        }
    }

    private static function loadDesiredIndexerSettings(): void {
        $cls = new ReflectionClass(static::class);
        self::$testedIndexerName = self::getTestedIndexerName($cls);
        self::$testedIndexerMode = self::getTestedIndexerMode($cls);
    }

    private static function getTestedIndexerName(ReflectionClass $cls): string {
        $docComment = $cls->getDocComment();
        if (str_contains($docComment, '@UsesProductIndexer')) {
            return ProductProcessor::INDEXER_ID;
        }
        if (str_contains($docComment, '@UsesCategoryIndexer')) {
            return CategoryProcessor::INDEXER_ID;
        }
        if (str_contains($docComment, '@UsesAttributeIndexer')) {
            return AttributeProcessor::INDEXER_ID;
        }
        throw new Exception("Cannot detect indexer to use for $cls");
    }

    private static function getTestedIndexerMode(ReflectionClass $cls): string {
        if ($cls->isSubclassOf(BaseAppEntityUpdateTest::class)) {
            return MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME;
        }
        if ($cls->isSubclassOf(BaseDirectDbEntityUpdateTest::class)) {
            // Magento creates triggers to save db-level changes only when the scheduler is in the below mode:
            return MagentoIndexerOperationsExecutor::UPDATE_BY_SCHEDULE_DISPLAY_NAME;
        }
        throw new Exception("Cannot detect desired indexer mode for $cls");
    }

    protected function setUp(): void {
        echo "Starting {$this->getName()}\n";
        $this->logFileUtils = new MagentoLogFileUtils();
        CodeCoverageReportGenerator::hideCoverageFilesFromPreviousTest();
    }

    protected function tearDown(): void {
        $ingestedKeys = $this->logFileUtils->getPublishedAndUnpublishedKeys();
        echo 'Keys ingested during the test:' . PHP_EOL;
        echo $ingestedKeys->formatted() . PHP_EOL;
        CodeCoverageReportGenerator::generateSingleTestCodeCoverageReport($this);
    }

    public static function productKey(EntityIds $productId, string $storeCode = self::STORE_1_CODE): string {
        return self::productKeyFromEntityId($productId->getEntityId(), $storeCode);
    }
    public static function productKeyFromEntityId(int $productEntityId, string $storeCode = self::STORE_1_CODE): string {
        return self::expectedStreamxKey($productEntityId, 'product', $storeCode);
    }

    public static function categoryKey(EntityIds $categoryId, string $storeCode = self::STORE_1_CODE): string {
        return self::categoryKeyFromEntityId($categoryId->getEntityId(), $storeCode);
    }
    public static function categoryKeyFromEntityId(int $categoryEntityId, string $storeCode = self::STORE_1_CODE): string {
        return self::expectedStreamxKey($categoryEntityId, 'category', $storeCode);
    }

    private static function expectedStreamxKey(int $entityId, string $type, string $storeCode): string {
        return "{$storeCode}_$type:$entityId";
    }

}
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
use StreamX\ConnectorCatalog\test\integration\utils\MagentoOperationsExecutor;

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
    protected const WEBSITE_2_CODE = 'second_website';
    protected const WEBSITE_2_STORE_CODE = 'store_for_second_website';

    protected static bool $areTestsInitialized = false;

    private static array $initialIndexerModes;

    // every test operates on one or more indexes which share the same mode
    private static string $testedIndexerMode;
    protected static array $testedIndexerNames;

    protected static MagentoMySqlQueryExecutor $db;
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
        MagentoEndpointsCaller::call('stores/setup');

        self::$db = new MagentoMySqlQueryExecutor();
        self::loadInitialIndexerModes();
        self::loadStoreAndWebsiteIds();

        if (self::$db->isEnterpriseMagento()) {
            self::disableGiftCardsCategory();
        }
    }

    private static function loadInitialIndexerModes(): void {
        foreach ([ProductProcessor::INDEXER_ID, CategoryProcessor::INDEXER_ID, AttributeProcessor::INDEXER_ID] as $indexerName) {
            self::$initialIndexerModes[$indexerName] = MagentoIndexerOperationsExecutor::getIndexerMode($indexerName);
        }
    }

    private static function loadStoreAndWebsiteIds(): void {
        self::$store2Id = self::loadIdByCode('store', 'store_id', self::STORE_2_CODE);
        self::$website2Id = self::loadIdByCode('store_website', 'website_id', self::WEBSITE_2_CODE);
        self::$website2StoreId = self::loadIdByCode('store', 'store_id', self::WEBSITE_2_STORE_CODE);
    }

    private static function loadIdByCode(string $table, string $column, string $code): int {
        return self::$db->selectSingleValue("SELECT $column FROM $table WHERE code = '$code'");
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
        foreach (self::$testedIndexerNames as $testedIndexerName) {
            if (self::$testedIndexerMode !== self::$initialIndexerModes[$testedIndexerName]) {
                MagentoIndexerOperationsExecutor::setIndexerMode($testedIndexerName, self::$testedIndexerMode);
            }
        }
    }

    private static function restoreIndexerModeInMagento(): void {
        foreach (self::$testedIndexerNames as $testedIndexerName) {
            $initialIndexerMode = self::$initialIndexerModes[$testedIndexerName];
            if (self::$testedIndexerMode !== $initialIndexerMode) {
                MagentoIndexerOperationsExecutor::setIndexerMode($testedIndexerName, $initialIndexerMode);
            }
        }
    }

    private static function loadDesiredIndexerSettings(): void {
        $cls = new ReflectionClass(static::class);
        self::$testedIndexerNames = self::getTestedIndexerNames($cls);
        self::$testedIndexerMode = self::getTestedIndexerMode($cls);
    }

    private static function getTestedIndexerNames(ReflectionClass $cls): array {
        $indexerNames = [];
        $docComment = $cls->getDocComment();
        if (str_contains($docComment, '@UsesProductIndexer')) {
            $indexerNames[] = ProductProcessor::INDEXER_ID;
        }
        if (str_contains($docComment, '@UsesCategoryIndexer')) {
            $indexerNames[] = CategoryProcessor::INDEXER_ID;
        }
        if (str_contains($docComment, '@UsesAttributeIndexer')) {
            $indexerNames[] = AttributeProcessor::INDEXER_ID;
        }
        return $indexerNames;
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
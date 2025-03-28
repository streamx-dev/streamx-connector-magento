<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

use Magento\Catalog\Model\Indexer\Product\Price\DimensionCollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\TableMaintainer;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\DefaultPrice;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\Factory;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\PriceInterface;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\DimensionalIndexerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use SplFixedArray;
use Zend_Db;

class PricesResolver {
    /**
     * Only default customer Group ID (0) is supported now
     */
    private const CUSTOMER_GROUP_ID = 0;

    private DefaultPrice $resource;
    private ScopeConfigInterface $config;
    private StoreManagerInterface $storeManager;
    private CurrencyFactory $currencyFactory;
    private TimezoneInterface $timezone;
    private DateTime $dateTime;
    private Type $catalogProductType;
    private Factory $indexerPriceFactory;
    private array $indexers = [];
    private DimensionCollectionFactory $dimensionCollectionFactory;
    private TableMaintainer $tableMaintainer;

    public function __construct(
        ScopeConfigInterface $config,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        TimezoneInterface $localeDate,
        DateTime $dateTime,
        Type $catalogProductType,
        Factory $indexerPriceFactory,
        DefaultPrice $defaultIndexerResource,
        DimensionCollectionFactory $dimensionCollectionFactory = null,
        TableMaintainer $tableMaintainer = null
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->currencyFactory = $currencyFactory;
        $this->timezone = $localeDate;
        $this->dateTime = $dateTime;
        $this->catalogProductType = $catalogProductType;
        $this->indexerPriceFactory = $indexerPriceFactory;
        $this->resource = $defaultIndexerResource;
        $this->dimensionCollectionFactory = $dimensionCollectionFactory ?? ObjectManager::getInstance()->get(
            DimensionCollectionFactory::class
        );
        $this->tableMaintainer = $tableMaintainer ?? ObjectManager::getInstance()->get(
            TableMaintainer::class
        );
    }

    public function loadIndexedPrices(array $changedIds): array {
        $this->prepareWebsiteDateTable();

        $productsTypes = $this->getProductsTypes($changedIds);
        $typeIndexers = $this->getTypeIndexers();
        /** The Type Indexers are:
         * - downloadable = {Magento\Downloadable\Model\ResourceModel\Indexer\Price}
         * - simple =       {Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\SimpleProductPrice}
         * - virtual =      {Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\SimpleProductPrice}
         * - configurable = {Magento\ConfigurableProduct\Model\ResourceModel\Product\Indexer\Price\Configurable}
         * - bundle =       {Magento\Bundle\Model\ResourceModel\Indexer\Price}
         * - grouped =      {Magento\GroupedProduct\Model\ResourceModel\Product\Indexer\Price\Grouped}
         **/

        $indexedPrices = [];
        $connection = $this->getConnection();

        foreach ($typeIndexers as $productType => $indexer) {
            $entityIds = $productsTypes[$productType] ?? [];
            if (!empty($entityIds) && $indexer instanceof DimensionalIndexerInterface) {
                foreach ($this->dimensionCollectionFactory->create() as $dimensions) {
                    $this->tableMaintainer->createMainTmpTable($dimensions); // resolves to: catalog_product_index_price_tmp
                    $temporaryTable = $this->tableMaintainer->getMainTmpTable($dimensions);
                    $this->clearTable($temporaryTable);
                    $indexer->executeByDimensions($dimensions, SplFixedArray::fromArray($entityIds, false));

                    // collect data inserted to the tmp table:
                    $select = $connection->select()->from($temporaryTable);
                    $indexedPrices[] = $connection->query($select)->fetchAll();
                }
            }
        }

        $result = [];
        foreach ($indexedPrices as $indexedPrice) {
            foreach ($indexedPrice as $indexedPriceRow) {
                if ($indexedPriceRow['customer_group_id'] === self::CUSTOMER_GROUP_ID) {
                    $result[$indexedPriceRow['entity_id']] = [
                        'price' => $indexedPriceRow['price'],
                        'final_price' => $indexedPriceRow['final_price']
                    ];
                }
            }
        }

        return $result;
    }

    private function prepareWebsiteDateTable(): void { // clears table and inserts fresh rows to catalog_product_index_website, with rate for date for each website
        $baseCurrency = $this->config->getValue(Currency::XML_PATH_CURRENCY_BASE);

        $select = $this->getConnection()->select()->from(
            ['cw' => $this->resource->getTable('store_website')],
            ['website_id']
        )->join(
            ['csg' => $this->resource->getTable('store_group')],
            'cw.default_group_id = csg.group_id',
            ['store_id' => 'default_store_id']
        )->where(
            'cw.website_id != 0'
        );

        $data = [];
        foreach ($this->getConnection()->fetchAll($select) as $item) {
            /** @var $website Website */
            $website = $this->storeManager->getWebsite($item['website_id']);

            if ($website->getBaseCurrencyCode() != $baseCurrency) {
                $rate = $this->currencyFactory->create()
                    ->load($baseCurrency)
                    ->getRate($website->getBaseCurrencyCode());
                if (!$rate) {
                    $rate = 1;
                }
            } else {
                $rate = 1;
            }

            /** @var $store Store */
            $store = $this->storeManager->getStore($item['store_id']);
            if ($store) {
                $timestamp = $this->timezone->scopeTimeStamp($store);
                $data[] = [
                    'website_id' => $website->getId(),
                    'website_date' => $this->dateTime->formatDate($timestamp, false),
                    'rate' => $rate,
                    'default_store_id' => $store->getId()
                ];
            }
        }

        $table = $this->resource->getTable('catalog_product_index_website');
        $this->clearTable($table);
        foreach ($data as $row) {
            $this->getConnection()->insertOnDuplicate($table, $row, array_keys($row));
        }
    }

    /**
     * Retrieve price indexers per product type
     * @return PriceInterface[]
     */
    private function getTypeIndexers(): array {
        if (empty($this->indexers)) {
            $types = $this->catalogProductType->getTypesByPriority();
            foreach ($types as $typeId => $typeInfo) {
                $modelName = $typeInfo['price_indexer'] ?? get_class($this->resource);

                $indexer = $this->indexerPriceFactory->create($modelName, ['fullReindexAction' => false]);
                // left setters for backward compatibility
                if ($indexer instanceof DefaultPrice) {
                    $indexer
                        ->setTypeId($typeId)
                        ->setIsComposite(!empty($typeInfo['composite']));
                }
                $this->indexers[$typeId] = $indexer;
            }
        }

        return $this->indexers;
    }

    private function getProductsTypes(array $changedIds): array {
        $select = $this->getConnection()->select()->from(
            $this->resource->getTable('catalog_product_entity'),
            ['entity_id', 'type_id']
        );
        $select->where('entity_id IN (?)', $changedIds, Zend_Db::INT_TYPE);
        $pairs = $this->getConnection()->fetchPairs($select);

        $byType = [];
        foreach ($pairs as $productId => $productType) {
            $byType[$productType][$productId] = $productId;
        }

        return $byType;
    }

    private function clearTable(string $table): void {
        $this->getConnection()->delete($table);
    }

    private function getConnection(): AdapterInterface {
        return $this->resource->getConnection();
    }

}

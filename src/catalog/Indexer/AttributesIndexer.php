<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\AttributeDataLoader;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;
use StreamX\ConnectorCore\System\GeneralConfig;
use Traversable;
use Zend_Db_Expr;
use Zend_Db_Select_Exception;

// TODO implement checking if only relevant attribute properties have changed to trigger publishing products
class AttributesIndexer extends BaseStreamxIndexer
{
    private const PRODUCT_ATTRIBUTE_TABLES = [
        'catalog_product_entity_datetime',
        'catalog_product_entity_decimal',
        'catalog_product_entity_gallery',
        'catalog_product_entity_int',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar'
    ];

    private ResourceConnection $resourceConnection;
    private ProductMetaData $productMetaData;
    private ProductsIndexer $productsIndexer;
    private ProductDataLoader $productDataLoader;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexableStoresProvider $indexableStoresProvider,
        AttributeDataLoader $dataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientConfiguration $clientConfiguration,
        IndexersConfigInterface $indexersConfig,
        ResourceConnection $resourceConnection,
        ProductMetaData $productMetaData,
        ProductsIndexer $productsIndexer,
        ProductDataLoader $productDataLoader
    ) {
        parent::__construct(
            $connectorConfig,
            $indexableStoresProvider,
            $dataLoader,
            $logger,
            $optimizationSettings,
            $clientConfiguration,
            $indexersConfig->getByName(AttributeProcessor::INDEXER_ID)
        );
        $this->resourceConnection = $resourceConnection;
        $this->productMetaData = $productMetaData;
        $this->productsIndexer = $productsIndexer;
        $this->productDataLoader = $productDataLoader;
    }

    /**
     * Override to instead of publishing attributes -> publish products that use those attributes
     * @param Traversable<AttributeDefinition> $attributeDefinitions
     */
    public function ingestEntities(Traversable $attributeDefinitions, int $storeId, StreamxClient $client): void {
        $changedAttributeIds = [];

        /** @var $attributeDefinition AttributeDefinition */
        foreach ($attributeDefinitions as $attributeDefinition) {
            if ($attributeDefinition === null) {
                // a deleted attribute. Currently, no way of collecting products that used it before
                continue;
            }
            $this->logger->info("Definition of attribute '{$attributeDefinition->getCode()}' has changed");
            $changedAttributeIds[] = $attributeDefinition->getId();
        }

        $productEntityIds = $this->loadEntityIdsOfProductThatUseAttributes($changedAttributeIds);
        if (empty($productEntityIds)) {
            // no changes in the attributes that should cause republishing products
            return;
        }

        $this->logger->info("Detected the following products to re-publish due to attribute definition change: " . json_encode($productEntityIds));
        $products = $this->productDataLoader->loadData($storeId, $productEntityIds);
        $this->productsIndexer->ingestEntities($products, $storeId, $client);
    }

    /**
     * @param int[] $attributeIds
     * @return int[]
     * @throws Zend_Db_Select_Exception
     */
    // TODO add conditions for products only from current website / active / visible
    private function loadEntityIdsOfProductThatUseAttributes(array $attributeIds): array {
        if (empty($attributeIds)) {
            return [];
        }

        $linkField = $this->productMetaData->get()->getLinkField();
        $connection = $this->resourceConnection->getConnection();

        $selectProductIdsQueries = [];
        foreach (self::PRODUCT_ATTRIBUTE_TABLES as $table) {
            // select values of all [row_id] or [entity_id] from each product attributes table
            $selectProductIdsQueries[] = $connection->select()
                ->from($this->resourceConnection->getTableName($table), [$linkField])
                ->distinct()
                ->where('attribute_id IN(?)', $attributeIds);
        }

        // union results of all the selects
        $selectProductIdsUnionQuery = $connection->select()->union($selectProductIdsQueries);

        // select actual entity_ids from main products table, that have the product ids found in all the product attribute tables
        $selectProductEntityIds = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id'])
            ->distinct()
            ->where("$linkField IN(?)", new Zend_Db_Expr($selectProductIdsUnionQuery))
            ->order('entity_id');

        return $connection->fetchCol($selectProductEntityIds);
    }

}
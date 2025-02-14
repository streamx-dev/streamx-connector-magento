<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCatalog\Indexer\ProductsIndexer;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Indexer\Action\ProductAction;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCore\Streamx\Client;
use Zend_Db_Expr;
use Zend_Db_Select_Exception;

// TODO implement checking if only relevant attribute properties have changed to trigger publishing products
class ProductsWithChangedAttributesIndexer
{
    private const PRODUCT_ATTRIBUTE_TABLES = [
        'catalog_product_entity_datetime',
        'catalog_product_entity_decimal',
        'catalog_product_entity_gallery',
        'catalog_product_entity_int',
        'catalog_product_entity_text',
        'catalog_product_entity_varchar'
    ];

    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;
    private ProductMetaData $productMetaData;
    private ProductsIndexer $productsIndexer;
    private ProductAction $action;

    public function __construct(
        LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        ProductMetaData $productMetaData,
        ProductsIndexer $productsIndexer,
        ProductAction $action
    ) {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->productMetaData = $productMetaData;
        $this->productsIndexer = $productsIndexer;
        $this->action = $action;
    }

    /**
     * @param array<int, ?AttributeDefinition> $attributeDefinitions key = attributeId, value = AttributeDefinition
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StreamxClientException
     * @throws Zend_Db_Select_Exception
     */
    public function process(array $attributeDefinitions, int $storeId, Client $client): void
    {
        $changedAttributeIds = [];
        foreach ($attributeDefinitions as $attributeDefinition) {
            if ($attributeDefinition === null) {
                // a deleted attribute. Currently, no way of collecting products that used it before
                continue;
            }
            $this->logger->info("Definition of attribute '{$attributeDefinition->getName()}' has changed");
            $changedAttributeIds[] = $attributeDefinition->getId();
        }

        if (empty($changedAttributeIds)) {
            // no changes in the attributes that should cause republishing products
            return;
        }

        $productEntityIds = $this->loadEntityIdsOfProductThatUseAttributes($changedAttributeIds);
        if (!empty($productEntityIds)) {
            $this->logger->info("Detected the following products to re-publish due to attribute definition change: " . json_encode($productEntityIds));

            $productsData = $this->action->loadData($storeId, $productEntityIds);
            $this->productsIndexer->saveIndex($productsData, $storeId, $client);
        }
    }

    /**
     * @param int[] $attributeIds
     * @return int[]
     * @throws Zend_Db_Select_Exception
     */
    private function loadEntityIdsOfProductThatUseAttributes(array $attributeIds): array
    {
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

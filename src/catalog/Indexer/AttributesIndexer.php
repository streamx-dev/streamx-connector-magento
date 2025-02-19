<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCatalog\Model\Indexer\Action\AttributeAction;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute\ProductsWithChangedAttributesIndexer;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Streamx\Client;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\System\GeneralConfig;

class AttributesIndexer extends BaseStreamxIndexer
{
    private ProductsWithChangedAttributesIndexer $productsWithChangedAttributesIndexer;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexableStoresProvider $indexableStoresProvider,
        AttributeAction $attributeAction,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        ClientResolver $clientResolver,
        IndexersConfigInterface $indexersConfig,
        ProductsWithChangedAttributesIndexer $productsWithChangedAttributesIndexer
    ) {
        parent::__construct(
            $connectorConfig,
            $indexableStoresProvider,
            $attributeAction,
            $logger,
            $optimizationSettings,
            $clientResolver,
            $indexersConfig->getByName(AttributeProcessor::INDEXER_ID)
        );
        $this->productsWithChangedAttributesIndexer = $productsWithChangedAttributesIndexer;
    }

    /**
     * Override to instead of publishing attributes -> redirect them to productsWithChangedAttributesIndexer
     * to publish products that use those attributes
     * @throws StreamxClientException
     */
    protected function processEntitiesBatch(array $entities, int $storeId, Client $client): void {
        $this->productsWithChangedAttributesIndexer->process($entities, $storeId, $client);
    }

}
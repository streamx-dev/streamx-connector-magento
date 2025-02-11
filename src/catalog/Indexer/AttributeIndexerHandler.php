<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute\ProductsWithChangedAttributesIndexer;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Streamx\Client;
use Traversable;

class AttributeIndexerHandler extends GenericIndexerHandler
{
    private ProductsWithChangedAttributesIndexer $productsWithChangedAttributesIndexer;

    public function __construct(
        OptimizationSettings $optimizationSettings,
        IndexersConfigInterface $indexersConfig,
        ProductsWithChangedAttributesIndexer $productsWithChangedAttributesIndexer
    ) {
        parent::__construct(
            $optimizationSettings,
            $indexersConfig->getByName(AttributeProcessor::INDEXER_ID)
        );
        $this->productsWithChangedAttributesIndexer = $productsWithChangedAttributesIndexer;
    }

    /**
     * Override to instead of publishing attributes -> redirect them to productsWithChangedAttributesIndexer
     * to publish products that use those attributes
     * @throws StreamxClientException
     */
    public function saveIndex(Traversable $documents, int $storeId, Client $client): void {
        $batchSize = $this->optimizationSettings->getBatchIndexingSize();

        foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
            $this->productsWithChangedAttributesIndexer->process($docs, $storeId, $client);
        }
    }

}
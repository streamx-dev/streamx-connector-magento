<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute\ProductsWithChangedAttributesIndexer;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\IndexOperations;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use Traversable;

class AttributeIndexerHandler extends GenericIndexerHandler
{
    private ProductsWithChangedAttributesIndexer $productsWithChangedAttributesIndexer;

    public function __construct(
        IndexOperations $indexOperations,
        LoggerInterface $logger,
        IndexersConfigInterface $indexersConfig,
        ProductsWithChangedAttributesIndexer $productsWithChangedAttributesIndexer
    ) {
        parent::__construct(
            $indexOperations,
            $logger,
            $indexersConfig->getByName(AttributeProcessor::INDEXER_ID)
        );
        $this->productsWithChangedAttributesIndexer = $productsWithChangedAttributesIndexer;
    }

    /**
     * Override to instead of publishing attributes -> redirect them to productsWithChangedAttributesIndexer
     * to publish products that use those attributes
     */
    public function saveIndex(Traversable $documents, int $storeId): void {
        $batchSize = $this->indexOperations->getBatchIndexingSize();

        foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
            $this->productsWithChangedAttributesIndexer->process($docs, $storeId);
        }
    }

}
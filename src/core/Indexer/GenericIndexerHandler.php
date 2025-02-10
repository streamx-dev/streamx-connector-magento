<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Index\IndexerDefinition;
use Magento\Framework\Indexer\SaveHandler\Batch;
use StreamX\ConnectorCore\Streamx\Client;
use Traversable;

class GenericIndexerHandler {

    protected Batch $batch;
    protected OptimizationSettings $optimizationSettings;
    private IndexerDefinition $indexerDefinition;

    public function __construct(
        OptimizationSettings $optimizationSettings,
        IndexerDefinition $indexerDefinition
    ) {
        $this->batch = new Batch();
        $this->optimizationSettings = $optimizationSettings;
        $this->indexerDefinition = $indexerDefinition;
    }

    /**
     * @throws StreamxClientException
     */
    public function saveIndex(Traversable $documents, int $storeId, Client $client): void {
        $batchSize = $this->optimizationSettings->getBatchIndexingSize();

        foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
            $this->processEntitiesBatch($docs, $storeId, $client);
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function processEntitiesBatch(array $entities, int $storeId, Client $client): void {
        $entitiesToPublish = [];
        $idsToUnpublish = [];
        foreach ($entities as $id => $entity) {
            if (empty($entity)) {
                $idsToUnpublish[] = $id;
            } else {
                $entitiesToPublish[$id] = $entity;
            }
        }

        if (!empty($entitiesToPublish)) {
            $this->addData($entitiesToPublish, $storeId);
            $client->publish(array_values($entitiesToPublish), $this->indexerDefinition->getName());
        }

        if (!empty($idsToUnpublish)) {
            $client->unpublish($idsToUnpublish, $this->indexerDefinition->getName());
        }
    }

    private function addData(array &$entities, int $storeId): void {
        foreach ($this->indexerDefinition->getDataProviders() as $dataProvider) {
            $entities = $dataProvider->addData($entities, $storeId);
        }
    }
}

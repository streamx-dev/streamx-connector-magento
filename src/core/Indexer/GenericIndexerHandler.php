<?php

namespace StreamX\ConnectorCore\Indexer;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use StreamX\ConnectorCore\Index\BulkRequest;
use StreamX\ConnectorCore\Index\IndexOperations;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Traversable;

class GenericIndexerHandler {

    protected Batch $batch;
    protected IndexOperations $indexOperations;
    private TypeInterface $indexType;
    private LoggerInterface $logger;

    public function __construct(
        IndexOperations $indexOperationProvider,
        LoggerInterface $logger,
        TypeInterface $indexType
    ) {
        $this->batch = new Batch();
        $this->indexOperations = $indexOperationProvider;
        $this->indexType = $indexType;
        $this->logger = $logger;
    }

    /**
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    public function saveIndex(Traversable $documents, int $storeId): void {
        try {
            $batchSize = $this->indexOperations->getBatchIndexingSize();

            foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
                $this->processDocsBatch($docs, $storeId);
            }
        } catch (ConnectionUnhealthyException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    private function processDocsBatch(array $docs, int $storeId): void {
        $entitiesToPublish = [];
        $idsToUnpublish = [];
        foreach ($docs as $id => $doc) {
            if (empty($doc)) {
                $idsToUnpublish[] = $id;
            } else {
                $entitiesToPublish[$id] = $doc;
            }
        }

        if (!empty($entitiesToPublish)) {
            $this->enrichDocs($entitiesToPublish, $storeId);
            $this->publishEntities(array_values($entitiesToPublish), $storeId);
        }

        if (!empty($idsToUnpublish)) {
            $this->unpublishEntities($idsToUnpublish, $storeId);
        }
    }

    private function enrichDocs(array &$docsToPublish, int $storeId): void {
        foreach ($this->indexType->getDataProviders() as $dataProvider) {
            $docsToPublish = $dataProvider->addData($docsToPublish, $storeId);
        }
    }

    /**
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    private function publishEntities(array $entities, int $storeId): void {
        $bulkRequest = BulkRequest::buildPublishRequest(
            $this->indexType->getName(),
            $entities
        );

        $this->indexOperations->executeBulk($storeId, $bulkRequest);
    }

    /**
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    private function unpublishEntities(array $ids, int $storeId): void {
        $bulkRequest = BulkRequest::buildUnpublishRequest(
            $this->indexType->getName(),
            $ids
        );

        $this->indexOperations->executeBulk($storeId, $bulkRequest);
    }
}

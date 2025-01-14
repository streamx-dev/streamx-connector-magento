<?php

namespace StreamX\ConnectorCore\Indexer;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Exception\ConnectionDisabledException;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use StreamX\ConnectorCore\Index\BulkRequest;
use StreamX\ConnectorCore\Index\Indicies\Config;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Store\Api\Data\StoreInterface;
use Traversable;

class GenericIndexerHandler {
    public const INDEX_IDENTIFIER = 'streamx_storefront_catalog';

    private Batch $batch;
    private Config $indicesConfig;
    private IndexOperationInterface $indexOperations;
    private string $typeName;
    private TypeInterface $type;
    private LoggerInterface $logger;

    public function __construct(
        IndexOperationInterface $indexOperationProvider,
        LoggerInterface $logger,
        Batch $batch,
        Config $indicesConfig,
        string $typeName
    ) {
        $this->batch = $batch;
        $this->indicesConfig = $indicesConfig;
        $this->indexOperations = $indexOperationProvider;
        $this->typeName = $typeName;
        $this->type = $this->loadType($typeName);
        $this->logger = $logger;
    }

    /**
     * @throws ConnectionUnhealthyException
     */
    public function saveIndex(Traversable $documents, StoreInterface $store): void {
        // TODO: don't try to do anything if the indexer is not enabled
        try {
            $storeId = (int)$store->getId();
            $batchSize = $this->indexOperations->getBatchIndexingSize();

            foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
                $this->processDocsBatch($docs, $storeId);
            }
        } catch (ConnectionDisabledException $exception) {
            // do nothing, StreamX indexer disabled in configuration
        } catch (ConnectionUnhealthyException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }
    }

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

        $entitiesToPublish = $this->enrichDocs($entitiesToPublish, $storeId);

        if (!empty($entitiesToPublish)) {
            $this->publishEntities(array_values($entitiesToPublish), $storeId);
        }

        if (!empty($idsToUnpublish)) {
            $this->unpublishEntities($idsToUnpublish, $storeId);
        }
    }

    private function enrichDocs(array $docsToPublish, int $storeId): array {
        foreach ($this->type->getDataProviders() as $dataProvider) {
            if (!empty($docsToPublish)) {
                $docsToPublish = $dataProvider->addData($docsToPublish, $storeId);
            }
        }
        return $docsToPublish;
    }

    private function publishEntities(array $entities, int $storeId): void {
        $bulkRequest = BulkRequest::buildPublishRequest(
            $this->typeName,
            $entities
        );

        $this->indexOperations->executeBulk($storeId, $bulkRequest);
    }

    private function unpublishEntities(array $ids, int $storeId): void {
        $bulkRequest = BulkRequest::buildUnpublishRequest(
            $this->typeName,
            $ids
        );

        $this->indexOperations->executeBulk($storeId, $bulkRequest);
    }

    private function loadType(string $typeName): TypeInterface {
        $indicesConfiguration = $this->indicesConfig->get();
        if (isset($indicesConfiguration[self::INDEX_IDENTIFIER])) {
            $config = $indicesConfiguration[self::INDEX_IDENTIFIER];
            $types = $config['types'];
            if (isset($types[$typeName])) {
                return $types[$typeName];
            }
            throw new InvalidArgumentException("Type $typeName is not available");
        }
        throw new LogicException('No configuration found');
    }
}

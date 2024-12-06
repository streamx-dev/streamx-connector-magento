<?php

namespace StreamX\ConnectorCore\Indexer;

use StreamX\ConnectorCore\Api\BulkLoggerInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Api\Indexer\TransactionKeyInterface;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Exception\ConnectionDisabledException;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use StreamX\ConnectorCore\Index\Index;
use StreamX\ConnectorCore\Index\IndexOperations;
use StreamX\ConnectorCore\Index\Indicies\Config;
use StreamX\ConnectorCore\Logger\IndexerLogger;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Store\Api\Data\StoreInterface;
use Traversable;

class GenericIndexerHandler
{
    private Batch $batch;
    public Config $indicesConfig;
    private IndexOperationInterface $indexOperations;
    private string $typeName;
    private IndexerLogger $indexerLogger;
    /**
     * @var int|string
     */
    private $transactionKey;
    private BulkLoggerInterface $bulkLogger;
    public array $types = [];
    public array $indicesByName = [];

    public function __construct(
        BulkLoggerInterface $bulkLogger,
        IndexOperationInterface $indexOperationProvider,
        IndexerLogger $indexerLogger,
        Batch $batch,
        Config $indicesConfig,
        TransactionKeyInterface $transactionKey,
        string $typeName
    ) {
        $this->bulkLogger = $bulkLogger;
        $this->batch = $batch;
        $this->indicesConfig = $indicesConfig;
        $this->indexOperations = $indexOperationProvider;
        $this->typeName = $typeName;
        $this->indexerLogger = $indexerLogger;
        $this->transactionKey = $transactionKey->load();
    }

    /**
     * Update documents in ES
     *
     * @return $this
     * @throws ConnectionUnhealthyException
     */
    public function updateIndex(Traversable $documents, StoreInterface $store, array $requireDataProvides)
    {
        try {
            $index = $this->getIndex($store);
            $type = $index->getType($this->typeName);
            $storeId = (int)$store->getId();
            $dataProviders = [];

            foreach ($type->getDataProviders() as $name => $dataProvider) {
                if (in_array($name, $requireDataProvides)) {
                    $dataProviders[] = $dataProvider;
                }
            }

            if (empty($dataProviders)) {
                return $this;
            }

            $batchSize = $this->indexOperations->getBatchIndexingSize();

            foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
                /** @var DataProviderInterface $datasource */
                foreach ($dataProviders as $datasource) {
                    if (!empty($docs)) {
                        $docs = $datasource->addData($docs, $storeId);
                    }
                }

                $bulkRequest = $this->indexOperations->createBulk()->updateDocuments(
                    $index->getName(),
                    $this->typeName,
                    $docs
                );

                $response = $this->indexOperations->executeBulk($storeId, $bulkRequest);
                $this->bulkLogger->log($response);
                $docs = null;
            }
        } catch (ConnectionDisabledException $exception) {
            // do nothing, ES indexer disabled in configuration
        } catch (ConnectionUnhealthyException $exception) {
            $this->indexerLogger->error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Save documents in ES
     *
     * @throws ConnectionUnhealthyException
     */
    public function saveIndex(Traversable $documents, StoreInterface $store): void
    {
        try {
            $index = $this->getIndex($store);
            $type = $index->getType($this->typeName);
            $storeId = (int)$store->getId();
            $batchSize = $this->indexOperations->getBatchIndexingSize();

            foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
                foreach ($type->getDataProviders() as $dataProvider) {
                    if (!empty($docs)) {
                        $docs = $dataProvider->addData($docs, $storeId);
                    }
                }

                if (!empty($docs)) {
                    $bulkRequest = $this->indexOperations->createBulk()->addDocuments(
                        $index->getName(),
                        $this->typeName,
                        $docs
                    );

                    $response = $this->indexOperations->executeBulk($storeId, $bulkRequest);
                    $this->bulkLogger->log($response);
                }

                $docs = null;
            }
        } catch (ConnectionDisabledException $exception) {
            // do nothing, ES indexer disabled in configuration
        } catch (ConnectionUnhealthyException $exception) {
            $this->indexerLogger->error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Removed unnecessary documents in ES by transaction key
     */
    public function cleanUpByTransactionKey(StoreInterface $store, array $docIds = null): void
    {
        try {
            $index = $this->getIndex($store); // TODO remove
            $transactionKeyQuery = ['must_not' => ['term' => ['tsk' => $this->transactionKey]]];
            $query = ['query' => ['bool' => $transactionKeyQuery]];

            if ($docIds) {
                $query['query']['bool']['must']['terms'] = ['_id' => array_values($docIds)];
            }

            $query = [
                'index' => $index->getName(), // TODO remove
                'type' => $this->typeName,
                'body' => $query,
            ];

            $this->indexOperations->deleteByQuery($store->getId(), $query);
        } catch (ConnectionDisabledException $exception) {
            // do nothing, ES indexer disabled in configuration
        }
    }

    private function getIndex(StoreInterface $store): Index
    {
        $indexName = $this->createIndexName($store);

        if (!isset($this->indicesByName[$indexName])) {
            $index = $this->initIndex($store);
            $this->indicesByName[$indexName] = $index;
        }

        return $this->indicesByName[$indexName];
    }

    private function createIndexName(StoreInterface $store): string
    {
        $indexNamePrefix = IndexOperations::INDEX_NAME_PREFIX;
        $storeIdentifier = (string)$store->getId();

        return $storeIdentifier
            ? $indexNamePrefix . '_' . $storeIdentifier
            : $indexNamePrefix;
    }

    private function initIndex(StoreInterface $store): Index
    {
        if (empty($this->types)) {
            $indicesConfiguration = $this->indicesConfig->get();
            if (!isset($indicesConfiguration[IndexOperations::INDEX_NAME_PREFIX])) {
                throw new \LogicException('No configuration found');
            }
            $config = $indicesConfiguration[IndexOperations::INDEX_NAME_PREFIX];
            $this->types = $config['types'];
        }

        $indexName = $this->createIndexName($store);
        return new Index($indexName, $this->types);
    }

    public function getTypeName(): string
    {
        return $this->typeName;
    }
}

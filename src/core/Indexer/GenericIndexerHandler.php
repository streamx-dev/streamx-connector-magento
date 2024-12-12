<?php

namespace StreamX\ConnectorCore\Indexer;

use InvalidArgumentException;
use LogicException;
use StreamX\ConnectorCore\Api\BulkLoggerInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Indexer\TransactionKey;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Exception\ConnectionDisabledException;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use StreamX\ConnectorCore\Index\Indicies\Config;
use StreamX\ConnectorCore\Logger\IndexerLogger;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Store\Api\Data\StoreInterface;
use Traversable;

class GenericIndexerHandler
{
    public const INDEX_IDENTIFIER = 'streamx_storefront_catalog';

    private Batch $batch;
    private Config $indicesConfig;
    private IndexOperationInterface $indexOperations;
    private string $typeName;
    private TypeInterface $type;
    private IndexerLogger $indexerLogger;
    /**
     * @var int|string
     */
    private $transactionKey;
    private BulkLoggerInterface $bulkLogger;

    public function __construct(
        BulkLoggerInterface $bulkLogger,
        IndexOperationInterface $indexOperationProvider,
        IndexerLogger $indexerLogger,
        Batch $batch,
        Config $indicesConfig,
        TransactionKey $transactionKey,
        string $typeName
    ) {
        $this->bulkLogger = $bulkLogger;
        $this->batch = $batch;
        $this->indicesConfig = $indicesConfig;
        $this->indexOperations = $indexOperationProvider;
        $this->typeName = $typeName;
        $this->type = $this->loadType($typeName);
        $this->indexerLogger = $indexerLogger;
        $this->transactionKey = $transactionKey->load();
    }

    /**
     * @return $this
     * @throws ConnectionUnhealthyException
     */
    public function updateIndex(Traversable $documents, StoreInterface $store, array $requireDataProvides)
    {
        try {
            $storeId = (int)$store->getId();
            $dataProviders = [];

            foreach ($this->type->getDataProviders() as $name => $dataProvider) {
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
     * @throws ConnectionUnhealthyException
     */
    public function saveIndex(Traversable $documents, StoreInterface $store): void
    {
        try {
            $storeId = (int)$store->getId();
            $batchSize = $this->indexOperations->getBatchIndexingSize();

            foreach ($this->batch->getItems($documents, $batchSize) as $docs) {
                foreach ($this->type->getDataProviders() as $dataProvider) {
                    if (!empty($docs)) {
                        $docs = $dataProvider->addData($docs, $storeId);
                    }
                }

                if (!empty($docs)) {
                    $bulkRequest = $this->indexOperations->createBulk()->addDocuments(
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
    public function cleanUpByIds(StoreInterface $store, array $docIds = null): void
    {
        try {
            $transactionKeyQuery = ['must_not' => ['term' => ['tsk' => $this->transactionKey]]];
            $query = ['query' => ['bool' => $transactionKeyQuery]];

            if ($docIds) {
                $query['query']['bool']['must']['terms'] = ['_id' => array_values($docIds)];
            }

            $query = [
                'type' => $this->typeName,
                'body' => $query,
            ];

            $this->indexOperations->deleteByQuery($store->getId(), $query);
        } catch (ConnectionDisabledException $exception) {
            // do nothing, ES indexer disabled in configuration
        }
    }

    public function loadType(string $typeName): TypeInterface
    {
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

<?php

namespace Divante\VsbridgeIndexerCore\Index;

use Divante\VsbridgeIndexerCore\Api\BulkResponseInterface;
use Divante\VsbridgeIndexerCore\Api\Client\ClientInterface;
use Divante\VsbridgeIndexerCore\Api\BulkResponseInterfaceFactory as BulkResponseFactory;
use Divante\VsbridgeIndexerCore\Api\BulkRequestInterface;
use Divante\VsbridgeIndexerCore\Api\BulkRequestInterfaceFactory as BulkRequestFactory;
use Divante\VsbridgeIndexerCore\Api\IndexInterface;
use Divante\VsbridgeIndexerCore\Api\IndexInterfaceFactory as IndexFactory;
use Divante\VsbridgeIndexerCore\Api\IndexOperationInterface;
use Divante\VsbridgeIndexerCore\Api\Index\TypeInterface;
use Divante\VsbridgeIndexerCore\Api\MappingInterface;
use Divante\VsbridgeIndexerCore\Config\OptimizationSettings;
use Divante\VsbridgeIndexerCore\Streamx\ClientResolver;
use Divante\VsbridgeIndexerCore\Exception\ConnectionUnhealthyException;
use Magento\Store\Api\Data\StoreInterface;

class IndexOperations implements IndexOperationInterface
{
    const GREEN_HEALTH_STATUS = 'green';

    const NUMBER_OF_REPLICAS_DURING_INDEXING = 0;

    const REFRESH_INTERVAL_DURING_INDEXING = -1;

    /**
     * @var ClientResolver
     */
    private $clientResolver;

    /**
     * @var IndexFactory
     */
    private $indexFactory;

    /**
     * @var BulkResponseFactory
     */
    private $bulkResponseFactory;

    /**
     * @var BulkRequestFactory
     */
    private $bulkRequestFactory;

    /**
     * @var IndexSettings
     */
    private $indexSettings;

    /**
     * @var array
     */
    private $indicesConfiguration;

    /**
     * @var array
     */
    private $indicesByIdentifier;

    /**
     * @var OptimizationSettings
     */
    private $optimizationSettings;

    public function __construct(
        ClientResolver $clientResolver,
        BulkResponseFactory $bulkResponseFactory,
        BulkRequestFactory $bulkRequestFactory,
        IndexSettings $indexSettings,
        IndexFactory $indexFactory,
        OptimizationSettings $optimizationSettings
    ) {
        $this->clientResolver = $clientResolver;
        $this->indexFactory = $indexFactory;
        $this->indexSettings = $indexSettings;
        $this->bulkResponseFactory = $bulkResponseFactory;
        $this->bulkRequestFactory = $bulkRequestFactory;
        $this->optimizationSettings = $optimizationSettings;
    }

    public function executeBulk(int $storeId, BulkRequestInterface $bulk): BulkResponseInterface
    {
        $this->checkEsCondition($storeId);

        if ($bulk->isEmpty()) {
            throw new \LogicException('Can not execute empty bulk.');
        }

        $bulkParams = ['body' => $bulk->getOperations()];
        $rawBulkResponse = $this->resolveClient($storeId)->bulk($bulkParams);

        return $this->bulkResponseFactory->create(
            ['rawResponse' => $rawBulkResponse]
        );
    }

    public function deleteByQuery(int $storeId, array $params): void
    {
        $this->resolveClient($storeId)->deleteByQuery($params);
    }

    public function indexExists(int $storeId, string $indexName): bool
    {
        $exists = true;

        if (!isset($this->indicesByIdentifier[$indexName])) {
            $exists = $this->resolveClient($storeId)->indexExists($indexName);
        }

        return $exists;
    }

    public function getIndexByName(string $indexIdentifier, StoreInterface $store): IndexInterface
    {
        $indexAlias = $this->getIndexAlias($store);

        if (!isset($this->indicesByIdentifier[$indexAlias])) {
            if (!$this->indexExists($store->getId(), $indexAlias)) {
                throw new \LogicException(
                    "{$indexIdentifier} index does not exist yet."
                );
            }

            $this->initIndex($indexIdentifier, $store, true);
        }

        return $this->indicesByIdentifier[$indexAlias];
    }

    public function getIndexAlias(StoreInterface $store): string
    {
        return $this->indexSettings->getIndexAlias($store);
    }

    public function createIndex(string $indexIdentifier, StoreInterface $store): IndexInterface
    {
        $index = $this->initIndex($indexIdentifier, $store, false);

        $this->resolveClient($store->getId())->createIndex(
            $index->getName(),
            $this->indexSettings->getEsConfig()
        );

        /** @var TypeInterface $type */
        foreach ($index->getTypes() as $type) {
            $mapping = $type->getMapping();

            if ($mapping instanceof MappingInterface) {
                $this->resolveClient($store->getId())->putMapping(
                    $index->getName(),
                    $type->getName(),
                    $mapping->getMappingProperties()
                );
            }
        }

        return $index;
    }

    public function refreshIndex(int $storeId, IndexInterface $index): void
    {
        $this->resolveClient($storeId)->refreshIndex($index->getName());
    }

    public function switchIndexer($storeId, string $indexName, string $indexAlias): void
    {
        $aliasActions = [
            [
                'add' => [
                    'index' => $indexName,
                    'alias' => $indexAlias,
                ]
            ]
        ];

        $deletedIndices = [];
        $oldIndices = $this->resolveClient($storeId)->getIndicesNameByAlias($indexAlias);

        foreach ($oldIndices as $oldIndexName) {
            if ($oldIndexName != $indexName) {
                $deletedIndices[] = $oldIndexName;
                $aliasActions[]   = [
                    'remove' => [
                        'index' => $oldIndexName,
                        'alias' => $indexAlias,
                    ]
                ];
            }
        }

        $this->resolveClient($storeId)->updateAliases($aliasActions);

        foreach ($deletedIndices as $deletedIndex) {
            $this->resolveClient($storeId)->deleteIndex($deletedIndex);
        }
    }

    /**
     * @param $indexIdentifier
     *
     * @return Index
     */
    private function initIndex($indexIdentifier, StoreInterface $store, bool $existingIndex)
    {
        $this->getIndicesConfiguration();

        if (!isset($this->indicesConfiguration[$indexIdentifier])) {
            throw new \LogicException('No configuration found');
        }

        $indexAlias = $this->getIndexAlias($store);
        $indexName = $this->indexSettings->createIndexName($store);

        if ($existingIndex) {
            $indexName = $indexAlias;
        }

        $config = $this->indicesConfiguration[$indexIdentifier];

        /** @var Index $index */
        $index = $this->indexFactory->create(
            [
                'name' => $indexName,
                'alias' => $indexAlias,
                'types' => $config['types'],
            ]
        );

        return $this->indicesByIdentifier[$indexAlias] = $index;
    }

    public function createBulk(): BulkRequestInterface
    {
        return $this->bulkRequestFactory->create();
    }

    public function getBatchIndexingSize(): int
    {
        return $this->indexSettings->getBatchIndexingSize();
    }

    /**
     * @return array
     */
    private function getIndicesConfiguration()
    {
        if (null === $this->indicesConfiguration) {
            $this->indicesConfiguration = $this->indexSettings->getIndicesConfig();
        }

        return $this->indicesConfiguration;
    }

    /**
     * @return ClientInterface
     */
    private function resolveClient(int $storeId): ClientInterface
    {
        return $this->clientResolver->getClient($storeId);
    }

    /**
     * @param $storeId
     *
     * @throws ConnectionUnhealthyException
     */
    private function checkEsCondition($storeId)
    {
        $clusterHealth = $this->resolveClient($storeId)->getClustersHealth();
        $this->checkClustersHealth($clusterHealth);
        $this->checkMaxBulkQueueRequirement($clusterHealth, $storeId);
    }

    /**
     * Check if clusters are in green status
     *
     * @param $clusterHealth
     *
     * @return array|void
     * @throws ConnectionUnhealthyException
     */
    private function checkClustersHealth($clusterHealth)
    {
        if ($this->optimizationSettings->checkClusterHealth()) {
            if ($clusterHealth[0]['status'] !== self::GREEN_HEALTH_STATUS) {
                $message = 'Can not execute bulk. Cluster health status is ' . $clusterHealth[0]['status'];
                throw new ConnectionUnhealthyException(__($message));
            }
        }

        return $clusterHealth;
    }

    /**
     * Check if pending tasks + batch indexer size (StreamxIndexer indices setting)
     * are lower than max bulk queue size master node
     *
     * @param $storeId
     *
     * @return void
     * @throws ConnectionUnhealthyException
     */
    private function checkMaxBulkQueueRequirement(array $clusterHealth, $storeId)
    {
        if ($this->optimizationSettings->checkMaxBulkQueueRequirement()) {
            $masterMaxQueueSize = $this->resolveClient($storeId)->getMasterMaxQueueSize();
            if (
                $masterMaxQueueSize &&
                $clusterHealth[0]['pending_tasks'] + $this->getBatchIndexingSize() > $masterMaxQueueSize
            ) {
                $message = 'Can not execute bulk. Pending tasks and batch indexing size is greater than max queue size';
                throw new ConnectionUnhealthyException(__($message));
            }
        }
    }

    /**
     * Set specific values before indexing to optimize ES
     */
    public function optimizeEsIndexing(int $storeId, string $indexName): void
    {
        if ($this->optimizationSettings->changeNumberOfReplicas()) {
            $this->resolveClient($storeId)->changeNumberOfReplicas(
                $indexName,
                self::NUMBER_OF_REPLICAS_DURING_INDEXING
            );
        }

        if ($this->optimizationSettings->changeRefreshInterval()) {
            $this->resolveClient($storeId)->changeRefreshInterval(
                $indexName,
                self::REFRESH_INTERVAL_DURING_INDEXING
            );
        }
    }

    /**
     * Restore values that were set before optimization.
     */
    public function cleanAfterOptimizeEsIndexing(int $storeId, string $indexName): void
    {
        if ($this->optimizationSettings->changeNumberOfReplicas()) {
            $numberOfReplicas = $this->optimizationSettings->getDefaultNumberOfReplicas();
            $this->resolveClient($storeId)->changeNumberOfReplicas($indexName, $numberOfReplicas);
        }

        if ($this->optimizationSettings->changeRefreshInterval()) {
            $refreshInterval = $this->optimizationSettings->getDefaultRefreshInterval();
            $this->resolveClient($storeId)->changeRefreshInterval($indexName, $refreshInterval);
        }
    }
}

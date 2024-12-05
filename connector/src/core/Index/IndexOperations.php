<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\BulkResponseInterface;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Api\BulkResponseInterfaceFactory as BulkResponseFactory;
use StreamX\ConnectorCore\Api\BulkRequestInterface;
use StreamX\ConnectorCore\Api\BulkRequestInterfaceFactory as BulkRequestFactory;
use StreamX\ConnectorCore\Api\IndexInterface;
use StreamX\ConnectorCore\Api\IndexInterfaceFactory as IndexFactory;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Api\MappingInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use Magento\Store\Api\Data\StoreInterface;

class IndexOperations implements IndexOperationInterface
{
    const GREEN_HEALTH_STATUS = 'green';

    private ClientResolver $clientResolver;
    private IndexFactory $indexFactory;
    private BulkResponseFactory $bulkResponseFactory;
    private BulkRequestFactory $bulkRequestFactory;
    private IndexSettings $indexSettings;
    private ?array $indicesConfiguration = null;
    private ?array $indicesByIdentifier = null;
    private OptimizationSettings $optimizationSettings;

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

    private function getIndicesConfiguration(): array
    {
        if (null === $this->indicesConfiguration) {
            $this->indicesConfiguration = $this->indexSettings->getIndicesConfig();
        }

        return $this->indicesConfiguration;
    }

    private function resolveClient(int $storeId): ClientInterface
    {
        return $this->clientResolver->getClient($storeId);
    }

    /**
     * @throws ConnectionUnhealthyException
     */
    private function checkEsCondition(int $storeId)
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
     * @throws ConnectionUnhealthyException
     */
    private function checkMaxBulkQueueRequirement(array $clusterHealth, $storeId): void
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
}

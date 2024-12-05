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
    private ?array $indicesByName = null;
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

    public function getIndex(StoreInterface $store): IndexInterface
    {
        $indexName = $this->indexSettings->createIndexName($store);

        if (!isset($this->indicesByName[$indexName])) {
            $this->initIndex($store);
        }

        return $this->indicesByName[$indexName];
    }

    public function createIndex(StoreInterface $store): IndexInterface
    {
        return $this->initIndex($store);
    }

    private function initIndex(StoreInterface $store): Index
    {
        if (null === $this->indicesConfiguration) {
            $this->indicesConfiguration = $this->indexSettings->getIndicesConfig();
        }

        if (!isset($this->indicesConfiguration[IndexSettings::INDEX_NAME_PREFIX])) {
            throw new \LogicException('No configuration found');
        }

        $indexName = $this->indexSettings->createIndexName($store);
        $config = $this->indicesConfiguration[IndexSettings::INDEX_NAME_PREFIX];

        /** @var Index $index */
        $index = $this->indexFactory->create(
            [
                'name' => $indexName,
                'types' => $config['types'],
            ]
        );

        return $this->indicesByName[$indexName] = $index;
    }

    public function createBulk(): BulkRequestInterface
    {
        return $this->bulkRequestFactory->create();
    }

    public function getBatchIndexingSize(): int
    {
        return $this->indexSettings->getBatchIndexingSize();
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
                // TODO: rewrite to StreamX-specific error message
                $message = 'Can not execute bulk. Cluster health status is ' . $clusterHealth[0]['status'];
                throw new ConnectionUnhealthyException(__($message));
            }
        }

        return $clusterHealth;
    }
}

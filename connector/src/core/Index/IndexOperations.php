<?php

namespace StreamX\ConnectorCore\Index;

use Magento\Framework\Intl\DateTimeFactory;
use StreamX\ConnectorCore\Api\BulkResponseInterface;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Api\BulkResponseInterfaceFactory as BulkResponseFactory;
use StreamX\ConnectorCore\Api\BulkRequestInterface;
use StreamX\ConnectorCore\Api\BulkRequestInterfaceFactory as BulkRequestFactory;
use StreamX\ConnectorCore\Api\IndexInterface;
use StreamX\ConnectorCore\Api\IndexInterfaceFactory as IndexFactory;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Config\IndicesSettings;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Index\Indicies\Config;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use Magento\Store\Api\Data\StoreInterface;

class IndexOperations implements IndexOperationInterface
{
    public const INDEX_NAME_PREFIX = 'streamx_storefront_catalog';
    const GREEN_HEALTH_STATUS = 'green';

    private Config $indicesConfig;
    private IndicesSettings $indicesSettings;
    private ClientResolver $clientResolver;
    private IndexFactory $indexFactory;
    private BulkResponseFactory $bulkResponseFactory;
    private BulkRequestFactory $bulkRequestFactory;
    private ?array $indicesConfiguration = null;
    private ?array $indicesByName = null;
    private OptimizationSettings $optimizationSettings;
    private DateTimeFactory $dateTimeFactory;

    public function __construct(
        Config               $indicesConfig,
        IndicesSettings      $indicesSettings,
        ClientResolver       $clientResolver,
        BulkResponseFactory  $bulkResponseFactory,
        BulkRequestFactory   $bulkRequestFactory,
        IndexFactory         $indexFactory,
        OptimizationSettings $optimizationSettings,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->indicesConfig = $indicesConfig;
        $this->indicesSettings = $indicesSettings;
        $this->clientResolver = $clientResolver;
        $this->indexFactory = $indexFactory;
        $this->bulkResponseFactory = $bulkResponseFactory;
        $this->bulkRequestFactory = $bulkRequestFactory;
        $this->optimizationSettings = $optimizationSettings;
        $this->dateTimeFactory = $dateTimeFactory;
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
        $indexName = $this->createIndexName($store);

        if (!isset($this->indicesByName[$indexName])) {
            $this->initIndex($store);
        }

        return $this->indicesByName[$indexName];
    }

    private function createIndexName(StoreInterface $store): string
    {
        $indexNamePrefix = self::INDEX_NAME_PREFIX;
        $storeIdentifier = (string)$store->getId();

        if ($storeIdentifier) {
            $indexNamePrefix .= '_' . $storeIdentifier;
        }

        $name = strtolower($indexNamePrefix);
        $currentDate = $this->dateTimeFactory->create();

        return $name . '_' . $currentDate->getTimestamp();
    }

    public function createIndex(StoreInterface $store): IndexInterface
    {
        return $this->initIndex($store);
    }

    private function initIndex(StoreInterface $store): Index
    {
        if (null === $this->indicesConfiguration) {
            $this->indicesConfiguration = $this->indicesConfig->get();
        }

        if (!isset($this->indicesConfiguration[self::INDEX_NAME_PREFIX])) {
            throw new \LogicException('No configuration found');
        }

        $indexName = $this->createIndexName($store);
        $config = $this->indicesConfiguration[self::INDEX_NAME_PREFIX];

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
        return $this->indicesSettings->getBatchIndexingSize();
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

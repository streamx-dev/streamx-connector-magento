<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;

class IndexOperations implements IndexOperationInterface
{
    const GREEN_HEALTH_STATUS = 'green';

    private ClientResolver $clientResolver;
    private OptimizationSettings $optimizationSettings;

    public function __construct(ClientResolver $clientResolver, OptimizationSettings $optimizationSettings) {
        $this->clientResolver = $clientResolver;
        $this->optimizationSettings = $optimizationSettings;
    }

    public function executeBulk(int $storeId, BulkRequest $bulk): BulkResponse
    {
        $this->checkEsCondition($storeId);

        if ($bulk->isEmpty()) {
            throw new \LogicException('Can not execute empty bulk.');
        }

        $bulkParams = ['body' => $bulk->getOperations()]; // TODO think of commonizing/improving names
        $rawBulkResponse = $this->resolveClient($storeId)->bulk($bulkParams);

        return new BulkResponse($rawBulkResponse);
    }

    public function getBatchIndexingSize(): int
    {
        return $this->optimizationSettings->getBatchIndexingSize();
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

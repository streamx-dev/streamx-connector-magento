<?php

namespace StreamX\ConnectorCore\Index;

use LogicException;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;

class IndexOperations implements IndexOperationInterface
{
    private ClientResolver $clientResolver;
    private OptimizationSettings $optimizationSettings;

    public function __construct(ClientResolver $clientResolver, OptimizationSettings $optimizationSettings) {
        $this->clientResolver = $clientResolver;
        $this->optimizationSettings = $optimizationSettings;
    }

    /**
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    public function executeBulk(int $storeId, BulkRequest $bulk): void
    {
        if ($bulk->isEmpty()) {
            throw new LogicException('Can not execute empty bulk.');
        }

        $client = $this->clientResolver->getClient($storeId);
        if ($this->optimizationSettings->shouldPerformStreamxAvailabilityCheck() && !$client->isStreamxAvailable()) {
            throw new ConnectionUnhealthyException(__('Can not execute bulk. StreamX is not available'));
        }

        $bulkOperations = $bulk->getOperations();
        $client->ingest($bulkOperations);
    }

    public function getBatchIndexingSize(): int
    {
        return $this->optimizationSettings->getBatchIndexingSize();
    }
}

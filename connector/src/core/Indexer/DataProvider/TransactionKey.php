<?php

namespace StreamX\ConnectorCore\Indexer\DataProvider;

use StreamX\ConnectorCore\Api\Indexer\TransactionKeyInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class TransactionKey implements DataProviderInterface
{
    private $transactionKey;

    public function __construct(TransactionKeyInterface $transactionKey)
    {
        $this->transactionKey = $transactionKey->load();
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        foreach ($indexData as &$data) {
            $data['tsk'] = $this->transactionKey;
        }

        return $indexData;
    }
}

<?php

namespace StreamX\ConnectorCore\Indexer\DataProvider;

use StreamX\ConnectorCore\Indexer\TransactionKey as IndexerTransactionKey;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class TransactionKey implements DataProviderInterface
{
    private $transactionKey;

    public function __construct(IndexerTransactionKey $transactionKey)
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

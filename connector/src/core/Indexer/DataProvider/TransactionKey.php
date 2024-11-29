<?php

namespace Divante\VsbridgeIndexerCore\Indexer\DataProvider;

use Divante\VsbridgeIndexerCore\Api\Indexer\TransactionKeyInterface;
use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;

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

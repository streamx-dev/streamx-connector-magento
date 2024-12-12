<?php

namespace StreamX\ConnectorCore\Indexer\DataProvider;

use DateTime;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class TransactionKey implements DataProviderInterface
{
    private int $transactionKey;

    public function __construct()
    {
        $this->transactionKey = (new DateTime())->getTimestamp();
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

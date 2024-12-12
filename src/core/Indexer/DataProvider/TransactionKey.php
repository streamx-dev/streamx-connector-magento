<?php

namespace StreamX\ConnectorCore\Indexer\DataProvider;

use StreamX\ConnectorCore\Api\DataProviderInterface;

class TransactionKey implements DataProviderInterface
{

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        foreach ($indexData as &$data) {
            $data['tsk'] = 1;
        }

        return $indexData;
    }
}

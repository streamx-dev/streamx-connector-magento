<?php

namespace StreamX\ConnectorCore\Indexer;

use StreamX\ConnectorCore\Api\Indexer\TransactionKeyInterface;

class TransactionKey implements TransactionKeyInterface
{
    /**
     * @var int|string
     */
    private $key;

    /**
     * @inheritdoc
     */
    public function load()
    {
        if (null === $this->key) {
            $currentDate = new \DateTime();
            $this->key = $currentDate->getTimestamp();
        }

        return $this->key;
    }
}

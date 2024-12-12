<?php

namespace StreamX\ConnectorCore\Indexer;


class TransactionKey
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

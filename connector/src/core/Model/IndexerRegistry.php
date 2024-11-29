<?php

namespace StreamX\ConnectorCore\Model;

class IndexerRegistry
{
    /**
     * @var bool
     */
    private $isFullReIndexationRunning = false;

    /**
     * @return bool
     */
    public function isFullReIndexationRunning()
    {
        return $this->isFullReIndexationRunning;
    }

    /**
     * @return void
     */
    public function setFullReIndexationIsInProgress()
    {
        $this->isFullReIndexationRunning = true;
    }
}

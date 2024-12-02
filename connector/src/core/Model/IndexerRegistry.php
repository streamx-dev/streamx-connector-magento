<?php

namespace StreamX\ConnectorCore\Model;

class IndexerRegistry
{
    private bool $isFullReIndexationRunning = false;

    public function isFullReIndexationRunning(): bool
    {
        return $this->isFullReIndexationRunning;
    }

    public function setFullReIndexationIsInProgress(): void
    {
        $this->isFullReIndexationRunning = true;
    }
}

<?php

namespace StreamX\ConnectorCore\Api;

interface BulkResponseInterface
{
    public function hasErrors(): bool;

    public function getErrorItems(): array;

    public function getSuccessItems(): array;

    public function aggregateErrorsByReason(): array;
}

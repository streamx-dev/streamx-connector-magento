<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Api;

interface BulkLoggerInterface
{
    public function log(BulkResponseInterface $bulkResponse): void;
}

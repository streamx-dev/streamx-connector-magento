<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

interface BulkLoggerInterface
{
    public function log(BulkResponseInterface $bulkResponse): void;
}

<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Api;

interface BulkLoggerInterface
{
    /**
     * @param BulkResponseInterface $bulkResponse
     *
     * @return void
     */
    public function log(BulkResponseInterface $bulkResponse);
}

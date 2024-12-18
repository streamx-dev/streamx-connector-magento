<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use StreamX\ConnectorCore\Index\BulkResponse;

interface BulkLoggerInterface {

    public function logErrors(BulkResponse $bulkResponse): void;
}

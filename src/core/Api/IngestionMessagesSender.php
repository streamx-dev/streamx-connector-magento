<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Streamx\Clients\Ingestion\Publisher\Message;

interface IngestionMessagesSender {

    /**
     * @param Message[] $ingestionMessages
     */
    public function send(array $ingestionMessages, int $storeId);
}
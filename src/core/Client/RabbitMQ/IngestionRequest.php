<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCore\Client\Model\Data;

class IngestionRequest {

    /** @var Message[] */
    private array $ingestionMessages;
    private int $storeId;

    public function __construct(array $ingestionMessages, int $storeId) {
        $this->ingestionMessages = $ingestionMessages;
        $this->storeId = $storeId;
    }

    /** @return Message[] */
    public function getIngestionMessages(): array {
        return $this->ingestionMessages;
    }

    public function getStoreId(): int {
        return $this->storeId;
    }

    public function toJson(): string {
        return json_encode(get_object_vars($this));
    }

    public static function fromJson(string $json): IngestionRequest {
        $jsonAsArray = json_decode($json, true);

        $ingestionMessages = array_map(
            function (array $message) {
                return self::createIngestionMessageFromArray($message);
            },
            $jsonAsArray['ingestionMessages']
        );

        $storeId = intval($jsonAsArray['storeId']);

        return new IngestionRequest($ingestionMessages, $storeId);
    }

    private static function createIngestionMessageFromArray(array $messageAsArray): Message {
        return new Message(
            $messageAsArray['key'],
            $messageAsArray['action'],
            $messageAsArray['eventTime'] ? intval($messageAsArray['eventTime']['long']) : null,
            (object)$messageAsArray['properties'],
            $messageAsArray['payload'] ? new Data($messageAsArray['payload']['content']['bytes']) : null
        );
    }

}
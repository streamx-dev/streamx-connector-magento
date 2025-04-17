<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;

/**
 * Sends Ingestion Requests to Rabbit MQ queue
 */
class RabbitMqIngestionRequestsSender {

    private LoggerInterface $logger;
    private RabbitMqConfiguration $rabbitMqConfiguration;

    public function __construct(LoggerInterface $logger, RabbitMqConfiguration $rabbitMqConfiguration) {
        $this->logger = $logger;
        $this->rabbitMqConfiguration = $rabbitMqConfiguration;
    }

    public function send(IngestionRequest $ingestionRequest) {
        $ingestionMessages = $ingestionRequest->getIngestionMessages();
        $storeId = $ingestionRequest->getStoreId();

        $messagesCount = count($ingestionMessages);
        $ingestionKeys = json_encode(self::getIngestionKeys($ingestionMessages));
        $this->logger->info("Sending $messagesCount messages with ingestion keys $ingestionKeys to RabbitMQ for store $storeId");

        $rabbitMqMessageBody = $ingestionRequest->toJson();
        $rabbitMqMessage = RabbitMqMessagesManager::createIngestionRequestMessage($rabbitMqMessageBody, $ingestionKeys);
        RabbitMqMessagesManager::sendIngestionRequestMessage($this->rabbitMqConfiguration, $rabbitMqMessage);
    }

    /**
     * @param Message[] $ingestionMessages
     * @return string[]
     */
    private static function getIngestionKeys(array $ingestionMessages): array {
        return array_map(
            function (Message $message) {
                return $message->key;
            },
            $ingestionMessages
        );
    }
}
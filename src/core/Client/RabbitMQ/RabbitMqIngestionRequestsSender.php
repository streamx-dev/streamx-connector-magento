<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCore\Api\IngestionMessagesSender;

/**
 * Sends Ingestion Requests to Rabbit MQ queue
 */
class RabbitMqIngestionRequestsSender extends BaseRabbitMqIngestionRequestsService implements IngestionMessagesSender {

    private LoggerInterface $logger;

    public function __construct(RabbitMqConfiguration $rabbitMqConfiguration, LoggerInterface $logger) {
        parent::__construct($rabbitMqConfiguration);
        $this->logger = $logger;
    }

    /**
     * @param Message[] $ingestionMessages
     */
    public function send(array $ingestionMessages, int $storeId) {
        $count = count($ingestionMessages);
        $this->logger->info("Sending $count messages to RabbitMQ");

        $ingestionRequest = new IngestionRequest($ingestionMessages, $storeId);
        $rabbitMqMessageBody = $ingestionRequest->toJson();

        $rabbitMqMessage = new AMQPMessage(
            $rabbitMqMessageBody,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        parent::sendMessage($rabbitMqMessage);
    }
}
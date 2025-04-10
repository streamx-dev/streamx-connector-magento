<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;

/**
 * Sends Ingestion Requests to Rabbit MQ queue
 */
class RabbitMqIngestionRequestsSender extends BaseRabbitMqIngestionRequestsService {

    public function __construct(
        LoggerInterface $logger,
        RabbitMqConfiguration $rabbitMqConfiguration
    ) {
        parent::__construct($logger, $rabbitMqConfiguration);
    }

    /**
     * @param Message[] $ingestionMessages
     */
    public function send(array $ingestionMessages, int $storeId) {
        $count = count($ingestionMessages);
        $this->logger->info("Sending $count messages to $this->routingKey");

        $ingestionRequest = new IngestionRequest($ingestionMessages, $storeId);
        $rabbitMqMessageBody = $ingestionRequest->toJson();

        $rabbitMqMessage = new AMQPMessage(
            $rabbitMqMessageBody,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        $this->channel->basic_publish($rabbitMqMessage, $this->exchange, $this->routingKey);
    }
}
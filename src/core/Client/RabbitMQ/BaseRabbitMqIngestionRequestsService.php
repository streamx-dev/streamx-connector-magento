<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Psr\Log\LoggerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class BaseRabbitMqIngestionRequestsService {

    protected LoggerInterface $logger;

    private AMQPStreamConnection $connection;
    protected AMQPChannel $channel;
    protected string $exchange;
    protected string $queueName;
    protected string $routingKey;

    protected function __construct(LoggerInterface $logger, RabbitMqConfiguration $configuration) {
        $this->logger = $logger;
        $this->exchange = 'streamx';
        $this->queueName = 'ingestion-requests';
        $this->routingKey = "$this->queueName.*";
        $this->connection = new AMQPStreamConnection(
            $configuration->getHost(),
            $configuration->getPort(),
            $configuration->getUser(),
            $configuration->getPassword()
        );
        $this->channel = $this->connection->channel();

        // Setup exchange and queue with binding (these calls skip creating their items if the items already exist)
        $this->channel->exchange_declare($this->exchange, 'topic', false, true, false);
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        $this->channel->queue_bind($this->queueName, $this->exchange, $this->routingKey);
    }

    public function __destruct() {
        $this->connection->close(); // internally, closes also all own channels
    }
}

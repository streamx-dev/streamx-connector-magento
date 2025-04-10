<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Usage: bin/magento streamx:consumer:start
 */
class RabbitMqIngestionRequestsConsumerStartCommand extends Command {

    public const COMMAND_NAME = 'streamx:consumer:start';
    private RabbitMqIngestionRequestsConsumer $consumer;

    public function __construct(RabbitMqIngestionRequestsConsumer $consumer) {
        $this->consumer = $consumer;
        parent::__construct();
    }

    protected function configure() {
        $this->setName(self::COMMAND_NAME);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Starting ' . get_class($this->consumer) . ' to listen for messages and consume them indefinitely');
        $this->consumer->startConsumingMessages();
    }
}
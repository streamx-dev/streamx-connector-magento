<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use Magento\Framework\Lock\LockManagerInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Usage: bin/magento streamx:consumer:start
 */
class RabbitMqIngestionRequestsConsumerStartCommand extends Command {

    public const COMMAND_NAME = 'streamx:consumer:start';
    private LoggerInterface $logger;
    private RabbitMqIngestionRequestsConsumer $consumer;
    private LockManagerInterface $lockManager;

    public function __construct(
        LoggerInterface $logger,
        RabbitMqIngestionRequestsConsumer $consumer,
        LockManagerInterface $lockManager
    ) {
        $this->logger = $logger;
        $this->consumer = $consumer;
        $this->lockManager = $lockManager;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure() {
        $this->setName(self::COMMAND_NAME);
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $loggerWrapper = new OutputInterfaceLoggerWrapper($output, $this->logger);
        $this->startConsumer($loggerWrapper);
        return Command::SUCCESS;
    }

    private function startConsumer(OutputInterfaceLoggerWrapper $loggerWrapper): void {
        $consumerClassName = get_class($this->consumer);
        $commandName = self::COMMAND_NAME;
        if ($this->lockManager->lock($commandName, 1)) {
            $loggerWrapper->info("Starting $consumerClassName to listen for messages and consume them indefinitely");
            $this->consumer->start($loggerWrapper);
        } else {
            $loggerWrapper->info("Previous instance of $consumerClassName is still running. You can find its PID using 'ps aux | grep $commandName' and kill it");
        }
    }

    /**
     * @inheritdoc
     */
    public function run(InputInterface $input, OutputInterface $output): int {
        $this->configureCtrlCExit();
        $exitCode = parent::run($input, $output);
        $this->onExit();
        return $exitCode;
    }

    private function onExit(): void {
        $this->lockManager->unlock(self::COMMAND_NAME);
    }

    private function configureCtrlCExit(): void {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function() {
            $this->onExit();
            exit(0);
        });
    }
}
<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use Magento\Framework\Lock\LockManagerInterface;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsConsumer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Usage: bin/magento streamx:consumer:start
 */
// TODO: automate starting this command
//  - add it to src/catalog/etc/crontab.xml
//  - launch it async to not block the cron execution
class RabbitMqIngestionRequestsConsumerStartCommand extends Command {

    public const COMMAND_NAME = 'streamx:consumer:start';
    private RabbitMqIngestionRequestsConsumer $consumer;
    private LockManagerInterface $lockManager;

    public function __construct(
        RabbitMqIngestionRequestsConsumer $consumer,
        LockManagerInterface $lockManager
    ) {
        $this->consumer = $consumer;
        $this->lockManager = $lockManager;
        parent::__construct();
    }

    protected function configure() {
        $this->setName(self::COMMAND_NAME);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $this->startConsumer($output);
        return Command::SUCCESS;
    }

    private function startConsumer(OutputInterface $output): void {
        $consumerClassName = get_class($this->consumer);
        if (!$this->lockManager->lock(self::COMMAND_NAME, 1)) {
            $output->writeln("Previous instance of $consumerClassName is still running, exiting.");
            return;
        }

        $output->writeln("Starting $consumerClassName to listen for messages and consume them indefinitely");
        $this->consumer->start($output);
    }

    public function run(InputInterface $input, OutputInterface $output): int {
        $this->configureCtrlCExit($output);
        $exitCode = parent::run($input, $output);
        $this->onExit($output);
        return $exitCode;
    }

    private function onExit(OutputInterface $output): void {
        $this->lockManager->unlock(self::COMMAND_NAME);
        $output->writeln('Command exited');
    }

    private function configureCtrlCExit(OutputInterface $output): void {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, function () use ($output) {
            $this->onExit($output);
            exit(0);
        });
    }
}
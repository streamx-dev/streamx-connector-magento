<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Console\Command\RabbitMqIngestionRequestsConsumerStartCommand;

/**
 * Starts RabbitMqIngestionRequestsConsumerStartCommand in a separate process.
 * Designed to be used in crontab.xml, so it's executed as part of "bin/magento cron:run"
 * The command should be internally secured to allow only a singleton running instance
 */
class RabbitMqIngestionRequestsConsumerStartCommandRunner {

    private LoggerInterface $logger;
    private DirectoryList $directoryList;

    public function __construct(LoggerInterface $logger, DirectoryList $directoryList) {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
    }

    public function runAsync(): void {
        $magentoRootDir = $this->directoryList->getRoot();
        $phpPath = PHP_BINARY;
        $consumerStartCommand = RabbitMqIngestionRequestsConsumerStartCommand::COMMAND_NAME;
        $additionalParams = ' > /dev/null 2>&1 &';

        $command = "cd $magentoRootDir && $phpPath bin/magento $consumerStartCommand $additionalParams";
        $this->logger->info("Running command $command");

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w']  // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
        if (is_resource($process)) {
            // close all streams immediately to not wait for process response
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } else {
            $this->logger->error("Failure executing command $command");
        }
    }
}
<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes messages to both given OutputInterface and LoggerInterface.
 * To be used in Magento Commands:
 *  - if a command is started from terminal, messages will be displayed to the terminal - and to log file
 *  - if a command is started programmatically as a process (without a terminal), messages will still be written to log file
 */
class OutputInterfaceLoggerWrapper {

    private OutputInterface $output;
    private LoggerInterface $logger;

    public function __construct(OutputInterface $output, LoggerInterface $logger) {
        $this->output = $output;
        $this->logger = $logger;
    }

    public function info(string $message): void {
        $this->output->writeln("<info>$message</info>");
        $this->logger->info($message);
    }

    public function error(string $message): void {
        $this->output->writeln("<error>$message</error>");
        $this->logger->error($message);
    }

    public function errorToCommandConsole(string $message): void {
        $this->output->writeln("<error>$message</error>");
    }
}
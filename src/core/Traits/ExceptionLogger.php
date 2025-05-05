<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Traits;

use Throwable;

trait ExceptionLogger {

    public function logExceptionAsError(string $customMessage, Throwable $e): void {
        $this->logger->error(
            "$customMessage: {$e->getMessage()}",
            [
                'Exception' => $e,
                'Stack trace' => $e->getTraceAsString(),
            ]
        );
    }
}
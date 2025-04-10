<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Traits;

use Exception;

trait ExceptionLogger {

    public function logExceptionAsError(string $customMessage, Exception $e): void {
        $this->logger->error(
            "$customMessage: {$e->getMessage()}",
            [
                'Exception' => $e,
                'Stack trace' => $e->getTraceAsString(),
            ]
        );
    }
}
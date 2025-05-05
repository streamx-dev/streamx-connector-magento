<?php declare(strict_types=1);

namespace StreamX\ConnectorTestEndpoints\Impl;

use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

/**
 * Intercepts exceptions thrown by endpoints and logs them to system log file
 */
class EndpointExceptionPlugin {
    use ExceptionLogger;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function beforeDispatch(Rest $subject, RequestInterface $request): void {
        $this->logger->info("Handling REST API {$request->getMethod()} at {$request->getUri()}");
    }

    public function afterDispatch(Rest $subject, Response $response) {
        if ($response->isException()) {
            foreach ($response->getException() as $exception) {
                $this->logExceptionAsError('Error response from endpoint', $exception);
            }
        }
        return $response;
    }
}
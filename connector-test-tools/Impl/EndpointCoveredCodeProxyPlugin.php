<?php declare(strict_types=1);

namespace StreamX\ConnectorTestTools\Impl;

use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class EndpointCoveredCodeProxyPlugin {

    private LoggerInterface $logger;
    private bool $isCoverageMeasurementEnabled;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $xdebugMode = getenv('XDEBUG_MODE');
        $this->isCoverageMeasurementEnabled = str_contains($xdebugMode, 'coverage');
    }

    public function beforeDispatch(Rest $subject, RequestInterface $request): void {
        $this->logger->info("Handling REST API execution at {$request->getUri()}");
        if ($this->isCoverageMeasurementEnabled) {
            xdebug_start_code_coverage();
        }
    }

    public function afterDispatch(Rest $subject, Response $response) {
        $coverageDestinationFile = __DIR__ . '/coverage.txt';
        if ($this->isCoverageMeasurementEnabled) {
            $coverage = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            $serializedCoverage = json_encode($coverage);
            file_put_contents($coverageDestinationFile, $serializedCoverage);
        } else {
            if (file_exists($coverageDestinationFile)) {
                unlink($coverageDestinationFile);
            }
        }
        return $response;
    }
}
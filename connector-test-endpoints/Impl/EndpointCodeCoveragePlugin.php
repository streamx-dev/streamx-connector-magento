<?php declare(strict_types=1);

namespace StreamX\ConnectorTestEndpoints\Impl;

use Magento\Framework\Webapi\Rest\Response;
use Magento\Webapi\Controller\Rest;
use Magento\Framework\App\RequestInterface;

/**
 * Starts measuring code coverage before executing endpoint and saves collected coverage to a file when execution ends
 */
class EndpointCodeCoveragePlugin {

    private bool $isCoverageMeasurementEnabled;

    public function __construct() {
        $xdebugMode = getenv('XDEBUG_MODE');
        $this->isCoverageMeasurementEnabled = str_contains($xdebugMode, 'coverage');
    }

    public function beforeDispatch(Rest $subject, RequestInterface $request): void {
        if ($this->isCoverageMeasurementEnabled) {
            xdebug_start_code_coverage();
        }
    }

    public function afterDispatch(Rest $subject, Response $response) {
        $coverageFilesDir = self::getCoverageFilesDir();
        $timestamp = floor(microtime(true) * 1000);
        $coverageDestinationFile = "$coverageFilesDir/$timestamp.txt";

        if ($this->isCoverageMeasurementEnabled) {
            $coverage = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            $serializedCoverage = json_encode($coverage);
            file_put_contents($coverageDestinationFile, $serializedCoverage);
        }
        return $response;
    }

    private static function getCoverageFilesDir(): string {
        $coverageFilesDir =__DIR__ . '/../coverage';
        if (!is_dir($coverageFilesDir)) {
            mkdir($coverageFilesDir, 0777, true);
        }
        return $coverageFilesDir;
    }
}
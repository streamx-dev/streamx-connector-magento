<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;

final class CodeCoverageReportGenerator {

    private const STREAMX_CONNECTOR_ROOT_DIR_IN_MAGENTO_SERVER = '/var/www/html/app/code/StreamX/Connector';

    public static function generateCodeCoverageReport(string $coverage, TestCase $caller): void {
        $localConnectorRootDir = DirectoryUtils::findFolder('streamx-connector-magento');

        $coverage = self::unwrapJsonFromString($coverage);
        $coverageArray = json_decode($coverage, true);
        $coverageArray = self::changeFilePathsToLocalPathsAndRemoveNonStreamxConnectorEntries($coverageArray, $localConnectorRootDir);

        $parsedCoverage = RawCodeCoverageData::fromXdebugWithoutPathCoverage($coverageArray);
        $codeCoverage = new CodeCoverage(new DriverMock(), new Filter());
        $codeCoverage->append($parsedCoverage, $caller);

        $htmlReportFacade = new Facade();
        $reportDirectory = self::createCoverageReportDirectory($localConnectorRootDir, $caller);
        $htmlReportFacade->process($codeCoverage, $reportDirectory);
    }

    private static function unwrapJsonFromString(string $coverage): string {
        $coverage = substr($coverage, 1, strlen($coverage) - 2);
        $coverage = str_replace('\\"', '"', $coverage);
        return str_replace('\\\\\\/', '/', $coverage);
    }

    private static function changeFilePathsToLocalPathsAndRemoveNonStreamxConnectorEntries(array $coverageData, string $localConnectorRootDir): array {
        $result = [];
        foreach ($coverageData as $magentoPhpFilePath => $data) {
            if (str_contains($magentoPhpFilePath, self::STREAMX_CONNECTOR_ROOT_DIR_IN_MAGENTO_SERVER)) {
                $localPhpFilePath = str_replace(
                    self::STREAMX_CONNECTOR_ROOT_DIR_IN_MAGENTO_SERVER,
                    $localConnectorRootDir,
                    $magentoPhpFilePath
                );
                $result[$localPhpFilePath] = $data;
            }
        }
        return $result;
    }

    private static function createCoverageReportDirectory(string $localConnectorRootDir, TestCase $caller): string {
        $className = str_replace('\\', '/', get_class($caller));
        $testName = $caller->getName();
        $dir = "$localConnectorRootDir/target/coverage-reports/$className/$testName";
        if (is_dir($dir)) {
            system("rm -rf " . escapeshellarg($dir));
        }
        mkdir($dir, 0777, true);
        return $dir;
    }
}

// SebastianBergmann\CodeCoverage\CodeCoverage class is mostly designed to measure coverage, so it needs xdebug driver.
// But for our use case it only parses already collected coverage data. So a Driver mock is enough
class DriverMock extends Driver {

    public function nameAndVersion(): string {
        throw new RuntimeException('Not implemented');
    }

    public function start(): void {
        throw new RuntimeException('Not implemented');
    }

    public function stop(): RawCodeCoverageData {
        throw new RuntimeException('Not implemented');
    }
}

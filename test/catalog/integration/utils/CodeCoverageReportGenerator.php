<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageDriverMock;
use StreamX\ConnectorCatalog\test\integration\utils\FileUtils;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;

final class CodeCoverageReportGenerator {

    private const STREAMX_CONNECTOR_ROOT_DIR_IN_MAGENTO_SERVER = '/var/www/html/app/code/StreamX/Connector';

    public static function generateCodeCoverageReport(string $coverage, TestCase $caller): void {
        $localConnectorRootDir = FileUtils::findFolder('streamx-connector-magento');

        $parsedCoverage = self::parseCoverage($coverage, $localConnectorRootDir);
        $codeCoverage = new CodeCoverage(new CodeCoverageDriverMock(), new Filter());
        $codeCoverage->append($parsedCoverage, $caller);

        $htmlReportFacade = new Facade();
        $reportDirectory = self::createCoverageReportDirectory($localConnectorRootDir, $caller);
        $htmlReportFacade->process($codeCoverage, $reportDirectory);
    }

    private static function parseCoverage(string $coverage, string $localConnectorRootDir): RawCodeCoverageData {
        $coverage = self::cleanUpJson($coverage);
        $coverage = self::changeFilePathsToLocalPaths($coverage, $localConnectorRootDir);
        $coverageArray = json_decode($coverage, true);
        $coverageArray = self::removeNonStreamxConnectorEntries($coverageArray, $localConnectorRootDir);
        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($coverageArray);
    }

    private static function cleanUpJson(string $escapedJson): string {
        $escapedJson = substr($escapedJson, 1, strlen($escapedJson) - 2); // unwrap from ' '
        $escapedJson = str_replace('\\"', '"', $escapedJson); // unescape double quotes
        return str_replace('\\\\\\/', '/', $escapedJson); // unescape slashes
    }

    private static function changeFilePathsToLocalPaths(string $coverage, string $localConnectorRootDir): string {
        return str_replace(
            self::STREAMX_CONNECTOR_ROOT_DIR_IN_MAGENTO_SERVER,
            $localConnectorRootDir,
            $coverage
        );
    }

    private static function removeNonStreamxConnectorEntries(array $coverageData, string $localConnectorRootDir): array {
        foreach ($coverageData as $filePath => $value) {
            if (!str_starts_with($filePath, $localConnectorRootDir)) {
                unset($coverageData[$filePath]);
            }
        }
        return $coverageData;
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
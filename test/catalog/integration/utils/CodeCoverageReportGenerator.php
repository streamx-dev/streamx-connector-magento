<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\BaseTestRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Report\Html\Facade;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;
use const true;

final class CodeCoverageReportGenerator {

    private const STREAMX_CONNECTOR_ROOT_DIR_IN_MAGENTO_SERVER = '/var/www/html/app/code/StreamX/Connector';

    public static function generateCodeCoverageReport(TestCase $caller): void {
        if (getenv('GENERATE_CODE_COVERAGE_REPORT') !== 'true') {
            return;
        }

        $localConnectorRootDir = FileUtils::findFolder('streamx-connector-magento');
        $coverageFilePath = "$localConnectorRootDir/magento/src/app/code/StreamX/ConnectorTestTools/Impl/coverage.txt";
        if (!file_exists($coverageFilePath)) {
            return;
        }

        $coverage = file_get_contents($coverageFilePath);
        if ($coverage === '[]') {
            return;
        }

        $testName = $caller->getName();
        $coveredData = self::toCleanedUpCoverageArray($coverage, $testName, $localConnectorRootDir);
        self::addMissingFilesAsUncovered($coveredData, $localConnectorRootDir);

        $uncoveredData = self::computeUncoveredData($coveredData);
        $codeCoverage = self::createCodeCoverageObject($testName, $coveredData, $uncoveredData);

        self::writeAsHtmlReport($localConnectorRootDir, $caller, $codeCoverage);
    }

    private static function toCleanedUpCoverageArray(string $coverage, string $testName, string $localConnectorRootDir): array {
        $coverage = str_replace('\\/', '/', $coverage); // unescape escaped slashes
        $coverage = self::changeFilePathsToLocalPaths($coverage, $localConnectorRootDir);
        $coverageArray = json_decode($coverage, true);
        foreach ($coverageArray as $file => &$coverageData) {
            foreach ($coverageData as $lineNumber => &$value) {
                if ($value === 1) {
                    $value = [0 => $testName];
                }
            }
        }
        return self::removeNonStreamxConnectorEntries($coverageArray, $localConnectorRootDir);
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
            if (!str_starts_with($filePath, "$localConnectorRootDir/src")) {
                unset($coverageData[$filePath]);
            }
        }
        return $coverageData;
    }

    // xdebug doesn't return any coverage info about files that were not executed at all
    private static function addMissingFilesAsUncovered(array &$coverageArray, string $localConnectorRootDir): void {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$localConnectorRootDir/src"));
        foreach ($iterator as $path) {
            if ($path->isFile() && $path->getExtension() === 'php') {
                $fullPath = $path->getRealPath();
                if (!array_key_exists($fullPath, $coverageArray)) {
                    $coverageArray[$fullPath] = [];
                }
            }
        }
    }

    private static function computeUncoveredData(array $coverageArray): ProcessedCodeCoverageData {
        $uncoveredData = new ProcessedCodeCoverageData();
        $analyser = new ParsingFileAnalyser(false, false);
        foreach (array_keys($coverageArray) as $filePath) {
            $rawCodeCoverageData = RawCodeCoverageData::fromUncoveredFile($filePath, $analyser);
            $uncoveredData->initializeUnseenData($rawCodeCoverageData);
        }
        return $uncoveredData;
    }

    private static function createCodeCoverageObject(string $testName, array $coveredData, ProcessedCodeCoverageData $uncoveredData): CodeCoverage {
        $codeCoverage = new CodeCoverage(new CodeCoverageDriverMock(), new Filter());

        $codeCoverage->setTests([
            $testName => [
                'size' => 'integration',
                'fromTestcase' => true,
                'status' => BaseTestRunner::STATUS_PASSED
            ]
        ]);

        $mergedCoverageData = new ProcessedCodeCoverageData();
        $mergedCoverageData->setLineCoverage($coveredData);
        $mergedCoverageData->merge($uncoveredData);

        $codeCoverage->setData($mergedCoverageData);
        return $codeCoverage;
    }

    private static function writeAsHtmlReport(string $localConnectorRootDir, TestCase $caller, CodeCoverage $codeCoverage): void {
        $htmlReportFacade = new Facade();
        $reportDirectory = self::createCoverageReportDirectory($localConnectorRootDir, $caller);
        $htmlReportFacade->process($codeCoverage, $reportDirectory);
    }

    private static function createCoverageReportDirectory(string $localConnectorRootDir, TestCase $caller): string {
        $className = str_replace('\\', '/', get_class($caller));
        $testName = $caller->getName();
        $dir = "$localConnectorRootDir/target/coverage-reports/$className/$testName";
        if (is_dir($dir)) {
            system("rm -rf " . escapeshellarg($dir));
        }
        mkdir($dir, 0777, true);
        echo "Code coverage report directory created at $dir\n";
        return $dir;
    }
}
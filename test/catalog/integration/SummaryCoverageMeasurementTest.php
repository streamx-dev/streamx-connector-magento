<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

class SummaryCoverageMeasurementTest extends TestCase {

    /** @test */
    public function shouldMeasureSummaryCoverage() {
        // This test should be executed after all tests are run and produced their coverage files.
        // The tests should be executed with xdebug mode on magento container set to "coverage" and with GENERATE_CODE_COVERAGE_REPORT=true local env var

        // when
        CodeCoverageReportGenerator::generateSummaryCodeCoverageReport($this);

        // then
        $classNameAsSubPath = str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));
        $expectedReportFile = __DIR__ . "/../../../target/coverage-reports/$classNameAsSubPath/shouldMeasureSummaryCoverage/index.html";
        $this->assertFileExists($expectedReportFile);
    }
}
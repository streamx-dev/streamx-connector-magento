<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

class SummaryCoverageMeasurementTest extends TestCase {

    protected function setUp(): void {
        if (!CodeCoverageReportGenerator::isCoverageMeasurementEnabledOnMagentoServer()) {
            self::markTestSkipped('Skipping test because coverage measurement is not enabled on Magento server');
        }
    }

    /** @test */
    public function shouldMeasureSummaryCoverage() {
        // given: This test should be executed after all (or at least one) tests are run and produced their coverage files

        // when
        CodeCoverageReportGenerator::generateSummaryCodeCoverageReport($this);

        // then
        $classNameAsSubPath = str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));
        $expectedReportFile = __DIR__ . "/../../../target/coverage-reports/$classNameAsSubPath/shouldMeasureSummaryCoverage/index.html";
        $this->assertFileExists($expectedReportFile);
    }
}
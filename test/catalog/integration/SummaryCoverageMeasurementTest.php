<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\FileUtils;

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
        $testClassNameWithSlashes = str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));
        $testName = $this->getName();
        $expectedReportFile = FileUtils::findFolder('target/coverage-reports') . "/$testClassNameWithSlashes/$testName/index.html";
        $this->assertFileExists($expectedReportFile);
    }
}
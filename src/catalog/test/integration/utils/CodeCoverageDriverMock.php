<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use RuntimeException;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * SebastianBergmann\CodeCoverage\CodeCoverage class is mostly designed to measure coverage, so it needs xdebug driver.
 * But for our use case it only parses already collected coverage data. So a Driver mock is enough
 */
class CodeCoverageDriverMock extends Driver {

    public function nameAndVersion(): string {
        throw self::newNotImplementedException();
    }

    public function start(): void {
        throw self::newNotImplementedException();
    }

    public function stop(): RawCodeCoverageData {
        throw self::newNotImplementedException();
    }

    private static function newNotImplementedException(): RuntimeException {
        return new RuntimeException('Not implemented, should never be called');
    }
}
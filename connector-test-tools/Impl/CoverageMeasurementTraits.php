<?php

namespace StreamX\ConnectorTestTools\Impl;

trait CoverageMeasurementTraits {

    protected function executeWithCoverageMeasurement(callable $customCode): string {
        $xdebugMode = ini_get('xdebug.mode');
        $isCoverageMeasurementEnabled = str_contains($xdebugMode, 'coverage');

        if ($isCoverageMeasurementEnabled) {
            xdebug_start_code_coverage();

            $customCode();

            $coverage = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();
            return json_encode($coverage);
        } else {
            return $this->executeWithoutCoverageMeasurement($customCode);
        }
    }

    private function executeWithoutCoverageMeasurement(callable $customCode): string {
        $customCode();
        return json_encode([]);
    }
}
<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

trait CoverageMeasurementTraits {

    protected function executeWithCoverageMeasurement(callable $customCode): string {
        $xdebugMode = getenv('XDEBUG_MODE');
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
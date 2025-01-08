<?php

namespace StreamX\ConnectorTestTools\Impl;

trait CoverageMeasurementTraits {

    protected function doWithCoverageMeasurement(callable $customCode): string {
        xdebug_start_code_coverage();

        $customCode();

        $coverage = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();
        return json_encode($coverage);
    }
}
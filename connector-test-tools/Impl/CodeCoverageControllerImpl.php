<?php

namespace StreamX\ConnectorTestTools\Impl;

use StreamX\ConnectorTestTools\Api\CodeCoverageControllerInterface;

class CodeCoverageControllerImpl implements CodeCoverageControllerInterface {

    /**
     * @inheritdoc
     */
    public function startCoverage() {
        xdebug_start_code_coverage();
    }

    /**
     * @inheritdoc
     */
    public function stopAndResetCoverage() {
        xdebug_stop_code_coverage(true);
    }

    /**
     * @inheritdoc
     */
    public function getCoverage() {
        return xdebug_get_code_coverage();
    }
}
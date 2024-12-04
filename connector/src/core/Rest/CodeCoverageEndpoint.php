<?php

namespace StreamX\ConnectorCore\Rest;

use StreamX\ConnectorCore\Api\Rest\CodeCoverageEndpointInterface;

class CodeCoverageEndpoint implements CodeCoverageEndpointInterface {

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
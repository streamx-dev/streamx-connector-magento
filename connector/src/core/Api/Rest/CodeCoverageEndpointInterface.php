<?php

namespace StreamX\ConnectorCore\Api\Rest;

interface CodeCoverageEndpointInterface {

    /**
     * Starts code coverage measurement
     * @return void
     */
    public function startCoverage();

    /**
     * Stops code coverage measurement and resets collected results
     * @return void
     */
    public function stopAndResetCoverage();

    /**
     * Returns collected code coverage measurement
     * @return mixed[] collected code coverage measurement
     */
    // note: the type used in @return PHPDoc block cannot be "array", due to Magento web api limitations
    public function getCoverage();
}

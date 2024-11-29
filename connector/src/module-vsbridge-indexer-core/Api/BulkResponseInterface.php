<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface BulkResponseInterface
{
    /**
     * @return boolean
     */
    public function hasErrors();

    /**
     * @return array
     */
    public function getErrorItems();

    /**
     * @return array
     */
    public function getSuccessItems();

    /**
     * @return array
     */
    public function aggregateErrorsByReason();
}

<?php

namespace StreamX\ConnectorTestEndpoints\Api;

use Exception;

interface MviewReindexerInterface {

    /**
     * Triggers processing new data from _cl tables subscribed by the given indexer's MView
     * @param string $indexerViewId view id (as in mview.xml file)
     * @return void
     * @throws Exception
     */
    public function reindexMview(string $indexerViewId): void;
}

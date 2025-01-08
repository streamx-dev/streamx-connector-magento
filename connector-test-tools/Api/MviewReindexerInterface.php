<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface MviewReindexerInterface {

    /**
     * Triggers processing new data from _cl tables subscribed by the given indexer's MView
     * @param string $indexerViewId view id (as in mview.xml file)
     * @return string collected code coverage, as Json String
     * @throws Exception
     */
    public function reindexMview(string $indexerViewId): string;
}

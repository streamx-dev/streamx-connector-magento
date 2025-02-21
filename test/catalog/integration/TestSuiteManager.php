<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

class TestSuiteManager implements TestListener {

    use TestListenerDefaultImplementation;

    private static array $initialIndexerModes = [];

    public function startTestSuite(TestSuite $suite): void {
        // TODO: currently the methods are executed before/after each test class
        //  first test class can be detected using a static counter
        //  TODO: how to detect last test class?
        foreach ([ProductProcessor::INDEXER_ID, CategoryProcessor::INDEXER_ID, AttributeProcessor::INDEXER_ID] as $indexerName) {
            $indexerOperations = new MagentoIndexerOperationsExecutor($indexerName);
            self::$initialIndexerModes[$indexerName] = $indexerOperations->getIndexerMode();
        }
    }

    public function endTestSuite(TestSuite $suite): void {
        foreach (self::$initialIndexerModes as $indexerName => $initialIndexerMode) {
            $indexerOperations = new MagentoIndexerOperationsExecutor($indexerName);
            $indexerOperations->setIndexerMode($initialIndexerMode);
        }
    }

}
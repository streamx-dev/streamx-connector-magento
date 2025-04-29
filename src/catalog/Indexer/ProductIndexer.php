<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Magento\Framework\Indexer\Config\DependencyInfoProviderInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\StreamxAvailabilityCheckerFactory;
use StreamX\ConnectorCore\Client\StreamxClientFactory;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexedStoresProvider;
use StreamX\ConnectorCore\System\GeneralConfig;

class ProductIndexer extends BaseStreamxIndexer {

    public const INDEXER_ID = 'streamx_product_indexer';

    private DependencyInfoProviderInterface $dependencyInfoProvider;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexedStoresProvider $indexedStoresProvider,
        ProductDataLoader $dataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientFactory $streamxClientFactory,
        StreamxAvailabilityCheckerFactory $streamxAvailabilityCheckerFactory,
        IndexerRegistry $indexerRegistry,
        IndexersConfigInterface $indexersConfig,
        DependencyInfoProviderInterface $dependencyInfoProvider
    ) {
        parent::__construct(
            $connectorConfig,
            $indexedStoresProvider,
            $dataLoader,
            $logger,
            $optimizationSettings,
            $streamxClientFactory,
            $streamxAvailabilityCheckerFactory,
            $indexerRegistry,
            $indexersConfig
        );
        $this->dependencyInfoProvider = $dependencyInfoProvider;
    }

    public function markIndexerAsInvalid(): void {
        $this->getIndexer()->invalidate();
    }

    public function reindexRow($id, $forceReindex = false): void {
        if ($this->hasToReindex()) {
            parent::reindexRow($id, $forceReindex);
        }
    }

    public function reindexList($ids, $forceReindex = false) {
        if ($this->hasToReindex()) {
            parent::reindexList($ids, $forceReindex);
        }
    }

    private function hasToReindex(): bool {
        $dependentIndexerIds = $this->dependencyInfoProvider->getIndexerIdsToRunBefore($this->getIndexerId());

        foreach ($dependentIndexerIds as $indexerId) {
            $dependentIndexer = $this->indexerRegistry->get($indexerId);
            if (!$dependentIndexer->isScheduled()) {
                return false;
            }
        }

        return true;
    }
}
<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Magento\Framework\Indexer\Config\DependencyInfoProviderInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\StreamxIndexerServices;

class ProductIndexer extends BaseStreamxIndexer {

    public const INDEXER_ID = 'streamx_product_indexer';

    private DependencyInfoProviderInterface $dependencyInfoProvider;

    public function __construct(
        StreamxIndexerServices $indexerServices,
        ProductDataLoader $dataLoader,
        DependencyInfoProviderInterface $dependencyInfoProvider
    ) {
        parent::__construct($indexerServices, $dataLoader);
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
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

    /**
     * @inheritdoc
     */
    public function reindexRow($id, $forceReindex = false): void {
        if ($this->areAllIndexersToRunBeforeInUpdateByScheduleMode()) {
            parent::reindexRow($id, $forceReindex);
        }
    }

    /**
     * @inheritdoc
     */
    public function reindexList($ids, $forceReindex = false, bool $checkStateOfIndexersToRunBefore = true) {
        if (!$checkStateOfIndexersToRunBefore || $this->areAllIndexersToRunBeforeInUpdateByScheduleMode()) {
            parent::reindexList($ids, $forceReindex);
        }
    }

    private function areAllIndexersToRunBeforeInUpdateByScheduleMode(): bool {
        $indexerIds = $this->dependencyInfoProvider->getIndexerIdsToRunBefore($this->getIndexerId());
        foreach ($indexerIds as $indexerId) {
            $indexer = $this->indexerRegistry->get($indexerId);
            if (!$indexer->isScheduled()) {
                return false;
            }
        }

        return true;
    }
}
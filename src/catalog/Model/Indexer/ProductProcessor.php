<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\Config\DependencyInfoProviderInterface;
use Magento\Framework\Indexer\IndexerRegistry;

class ProductProcessor extends \Magento\Framework\Indexer\AbstractProcessor
{
    /**
     * Indexer ID
     */
    public const INDEXER_ID = 'streamx_product_indexer';

    private DependencyInfoProviderInterface $dependencyInfoProvider;

    public function __construct(
        DependencyInfoProviderInterface $dependencyInfoProvider,
        IndexerRegistry $indexerRegistry
    ) {
        parent::__construct($indexerRegistry);
        $this->dependencyInfoProvider = $dependencyInfoProvider;
    }

    public function markIndexerAsInvalid(): void
    {
        $this->getIndexer()->invalidate();
    }

    /**
     * Run Row reindex
     *
     * @param int $id
     * @param bool $forceReindex
     */
    public function reindexRow($id, $forceReindex = false): void
    {
        if ($this->hasToReindex()) {
            parent::reindexRow($id, $forceReindex);
        }
    }

    /**
     * @param int[] $ids
     * @param bool $forceReindex
     */
    public function reindexList($ids, $forceReindex = false)
    {
        if ($this->hasToReindex()) {
            parent::reindexList($ids, $forceReindex);
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    private function hasToReindex(): bool
    {
        $hasToRun = true;
        $dependentIndexerIds = $this->dependencyInfoProvider->getIndexerIdsToRunBefore($this->getIndexerId());

        foreach ($dependentIndexerIds as $indexerId) {
            $dependentIndexer = $this->indexerRegistry->get($indexerId);

            if (!$dependentIndexer->isScheduled()) {
                $hasToRun = false;
                break;
            }
        }

        return $hasToRun;
    }
}

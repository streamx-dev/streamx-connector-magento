<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\AbstractProcessor;
use Magento\Framework\Indexer\Config\DependencyInfoProviderInterface;
use Magento\Framework\Indexer\IndexerRegistry;

class ProductProcessor extends AbstractProcessor
{
    /**
     * @override field from base class
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
     * @throws NoSuchEntityException
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
     * @throws NoSuchEntityException
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
        $dependentIndexerIds = $this->dependencyInfoProvider->getIndexerIdsToRunBefore($this->getIndexerId());

        // TODO: verify if the below condition is always safe:
        //   if any of the indexers to run before is in Update On Save mode -> our products indexer will not be executed
        return $this->areAllIndexersInUpdateByScheduleMode($dependentIndexerIds);
    }

    private function areAllIndexersInUpdateByScheduleMode(array $indexerIds): bool
    {
        foreach ($indexerIds as $indexerId) {
            $indexer = $this->indexerRegistry->get($indexerId);
            if (!$indexer->isScheduled()) {
                return false;
            }
        }

        return true;
    }
}

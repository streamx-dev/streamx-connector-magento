<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use Magento\Framework\Indexer\IndexerInterface;

trait StreamxIndexerCommandTraits {

    /**
     * @return IndexerInterface[]
     */
    public function getStreamxIndexers(): array {
        $indexers = $this->getAllIndexers();

        $streamxIndexers = [];
        foreach ($indexers as $indexer) {
            if (str_starts_with($indexer->getId(), 'streamx_')) {
                $streamxIndexers[] = $indexer;
            }
        }
        return $streamxIndexers;
    }

    private function getStreamxIndex($code): ?IndexerInterface
    {
        $indexers = $this->getStreamxIndexers();

        foreach ($indexers as $indexer) {
            $indexId = $indexer->getId();

            if ($code === $indexId) {
                return $indexer;
            }
        }

        return null;
    }

}

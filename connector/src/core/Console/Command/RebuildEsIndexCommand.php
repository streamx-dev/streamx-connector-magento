<?php

namespace StreamX\ConnectorCore\Console\Command;

use StreamX\ConnectorCore\Indexer\StoreManager;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Console\Command\AbstractIndexerCommand;
use Magento\Store\Api\Data\StoreInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Store\Model\StoreManagerInterface;

// TODO removal candidate
class RebuildEsIndexCommand extends AbstractIndexerCommand
{
    const INPUT_STORE = 'store';

    const INPUT_ALL_STORES = 'all';

    /**
     * @var IndexOperationInterface
     */
    private $indexOperations;

    /**
     * @var StoreManager
     */
    private $indexerStoreManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var array
     */
    private $excludeIndices = [];

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        ObjectManagerFactory $objectManagerFactory,
        ManagerInterface $eventManager, // Proxy
        array $excludeIndices = []
    ) {
        $this->excludeIndices = $excludeIndices;
        parent::__construct($objectManagerFactory);
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('streamx:reindex')
            ->setDescription('Rebuild indexer in ES.');

        $this->addOption(
            self::INPUT_STORE,
            null,
            InputOption::VALUE_REQUIRED,
            'Store ID or Store Code'
        );

        $this->addOption(
            self::INPUT_ALL_STORES,
            null,
            InputOption::VALUE_NONE,
            'Reindex all allowed stores (base on streamx configuration)'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initObjectManager();
        $output->setDecorated(true);
        $storeId = $input->getOption(self::INPUT_STORE);
        $allStores = $input->getOption(self::INPUT_ALL_STORES);

        $invalidIndices = $this->getInvalidIndices();

        if (!empty($invalidIndices)) {
            $message = 'Some indices has invalid status: '. implode(', ', $invalidIndices) . '. ';
            $message .= 'Please change indices status to VALID manually or use bin/magento streamx:reset command.';
            $output->writeln("<info>WARNING: Indexation can't be executed. $message</info>");
            return;
        }

        if (!$storeId && !$allStores) {
            $output->writeln(
                "<comment>Not enough information provided, nothing has been reindexed. Try using --help for more information.</comment>"
            );
        } else {
            $this->reindex($output, $storeId, $allStores);
        }
    }

    private function getInvalidIndices(): array
    {
        $invalid = [];

        foreach ($this->getIndexers() as $indexer) {
            if ($indexer->isWorking()) {
                $invalid[] = $indexer->getTitle();
            }
        }

        return $invalid;
    }

    /***
     * @param $storeId
     * @param $allStores
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function reindex(OutputInterface $output, $storeId, $allStores): int
    {
        $this->eventManager->dispatch('streamx_indexer_reindex_before', [
            'storeId' => $storeId,
            'allStores' => $allStores,
        ]);

        if ($storeId) {
            $store = $this->getStoreManager()->getStore($storeId);
            $returnValue = false;

            if ($this->isAllowedToReindex($store)) {
                $output->writeln("<info>Reindexing all StreamX indexes for store " . $store->getName() . "...</info>");
                $returnValue = $this->reindexStore($store, $output);
                $output->writeln("<info>Reindexing has completed!</info>");
            } else {
                $output->writeln("<info>Store " . $store->getName() . " is not allowed.</info>");
            }

            return $returnValue;
        } elseif ($allStores) {
            $output->writeln("<info>Reindexing all stores...</info>");
            $returnValues = [];
            $allowedStores = $this->getStoresAllowedToReindex();

            /** @var \Magento\Store\Api\Data\StoreInterface $store */
            foreach ($allowedStores as $store) {
                $output->writeln("<info>Reindexing store " . $store->getName() . "...</info>");
                $returnValues[] = $this->reindexStore($store, $output);
            }

            $output->writeln("<info>All stores have been reindexed!</info>");

            // If failure returned in any store return failure now
            return in_array(Cli::RETURN_FAILURE, $returnValues) ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
        }

        $this->eventManager->dispatch('streamx_indexer_reindex_after', [
            'storeId' => $storeId,
            'allStores' => $allStores,
        ]);
    }

    /**
     * Check if Store is allowed to reindex
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function isAllowedToReindex(\Magento\Store\Api\Data\StoreInterface $store): bool
    {
        $allowedStores = $this->getStoresAllowedToReindex();

        foreach ($allowedStores as $allowedStore) {
            if ($store->getId() === $allowedStore->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reindex each streamx index for the specified store
     */
    private function reindexStore(StoreInterface $store, OutputInterface $output): int
    {
        $this->getIndexerStoreManager()->override([$store]);
        $index = $this->getIndexOperations()->createIndex($store);

        $returnValue = Cli::RETURN_FAILURE;

        foreach ($this->getIndexers() as $indexer) {
            try {
                $startTime = microtime(true);
                $indexer->reindexAll();

                $resultTime = microtime(true) - $startTime;
                $output->writeln(
                    $indexer->getTitle() . ' index has been rebuilt successfully in ' . gmdate('H:i:s', $resultTime)
                );
                $returnValue = Cli::RETURN_SUCCESS;
            } catch (LocalizedException $e) {
                $output->writeln("<error>" . $e->getMessage() . "</error>");
            } catch (\Exception $e) {
                $output->writeln("<error>" . $indexer->getTitle() . ' indexer process unknown error:</error>');
                $output->writeln("<error>" . $e->getMessage() . "</error>");
            }
        }

        $output->writeln(
            sprintf('<info>Index name: %s</info>', $index->getName())
        );

        return $returnValue;
    }

    /**
     * @return IndexerInterface[]
     */
    private function getIndexers()
    {
        /** @var IndexerInterface[] */
        $indexers = $this->getAllIndexers();
        $streamxIndexers = [];

        foreach ($indexers as $indexer) {
            $indexId = $indexer->getId();

            if (substr($indexId, 0, 9) === 'streamx_' && !in_array($indexId, $this->excludeIndices)) {
                $streamxIndexers[] = $indexer;
            }
        }

        return $streamxIndexers;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoresAllowedToReindex(): array
    {
        return $this->getIndexerStoreManager()->getStores();
    }

    /**
     * @return StoreManagerInterface
     */
    private function getStoreManager()
    {
        if (null === $this->storeManager) {
            $this->storeManager = $this->getObjectManager()->get(StoreManagerInterface::class);
        }

        return $this->storeManager;
    }

    /**
     * @return StoreManager
     */
    private function getIndexerStoreManager()
    {
        if (null === $this->indexerStoreManager) {
            $this->indexerStoreManager = $this->getObjectManager()->get(StoreManager::class);
        }

        return $this->indexerStoreManager;
    }

    /**
     * @return IndexOperationInterface
     */
    private function getIndexOperations()
    {
        if (null === $this->indexOperations) {
            $this->indexOperations = $this->getObjectManager()->get(IndexOperationInterface::class);
        }

        return $this->indexOperations;
    }

    /**
     * Initiliaze object manager
     */
    private function initObjectManager()
    {
        $this->getObjectManager();
    }
}

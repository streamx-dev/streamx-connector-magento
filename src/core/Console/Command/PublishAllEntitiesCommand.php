<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Indexer\Console\Command\AbstractIndexerCommand;
use Magento\Store\Api\Data\StoreInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Usage:
 *      bin/magento streamx:reindex --store=1
 *    where 1 is the store ID
 * or
 *      bin/magento streamx:reindex --all
 */
class PublishAllEntitiesCommand extends AbstractIndexerCommand
{
    use StreamxIndexerCommandTraits;

    const DESCRIPTION = 'Sends all data to StreamX';

    const INPUT_STORE_OPTION_NAME = 'store';
    const INPUT_ALL_STORES_OPTION_NAME = 'all';

    private IndexableStoresProvider $indexableStoresProvider;
    private StoreManagerInterface $storeManager;
    private ManagerInterface $eventManager;

    public function __construct(
        ObjectManagerFactory $objectManagerFactory,
        ManagerInterface $eventManager, // Proxy
        IndexableStoresProvider $indexableStoresProvider,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($objectManagerFactory);
        $this->eventManager = $eventManager;
        $this->indexableStoresProvider = $indexableStoresProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('streamx:reindex')
            ->setDescription(self::DESCRIPTION);

        $this->addOption(
            self::INPUT_STORE_OPTION_NAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Store ID or Store Code'
        );

        $this->addOption(
            self::INPUT_ALL_STORES_OPTION_NAME,
            null,
            InputOption::VALUE_NONE,
            'Reindex all allowed stores (base on streamx configuration)'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setDecorated(true);
        $storeId = $input->getOption(self::INPUT_STORE_OPTION_NAME);
        $allStores = $input->getOption(self::INPUT_ALL_STORES_OPTION_NAME);

        $invalidIndices = $this->getInvalidIndexes();

        if (!empty($invalidIndices)) {
            $message = 'Some indices has invalid status: '. implode(', ', $invalidIndices) . '. ';
            $message .= 'Please change indices status to VALID manually or use bin/magento streamx:reset command.';
            $output->writeln("<info>WARNING: Indexation can't be executed. $message</info>");
            return -1;
        }

        if (!$storeId && !$allStores) {
            $output->writeln(
                "<comment>Not enough information provided, nothing has been reindexed. Try using --help for more information.</comment>"
            );
            return -1;
        }

        $this->reindex($output, $storeId, $allStores);
        return 0;
    }

    private function getInvalidIndexes(): array
    {
        $invalid = [];

        foreach ($this->getStreamxIndexers() as $indexer) {
            if ($indexer->isWorking()) {
                $invalid[] = $indexer->getTitle();
            }
        }

        return $invalid;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function reindex(OutputInterface $output, $storeId, $allStores): int
    {
        $this->eventManager->dispatch('streamx_indexer_reindex_before', [
            'storeId' => $storeId,
            'allStores' => $allStores,
        ]);

        if ($storeId) {
            $store = $this->storeManager->getStore($storeId);
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

            /** @var StoreInterface $store */
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
     */
    private function isAllowedToReindex(StoreInterface $store): bool
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
        $this->indexableStoresProvider->override([$store]);

        $returnValue = Cli::RETURN_FAILURE;

        foreach ($this->getStreamxIndexers() as $indexer) {
            try {
                $startTime = microtime(true);
                $indexer->reindexAll();

                $resultTime = microtime(true) - $startTime;
                $output->writeln(
                    $indexer->getTitle() . ' index has been rebuilt successfully in ' . gmdate('H:i:s', (int) $resultTime)
                );
                $returnValue = Cli::RETURN_SUCCESS;
            } catch (LocalizedException $e) {
                $output->writeln("<error>" . $e->getMessage() . "</error>");
            } catch (Exception $e) {
                $output->writeln("<error>" . $indexer->getTitle() . ' indexer process unknown error:</error>');
                $output->writeln("<error>" . $e->getMessage() . "</error>");
            }
        }

        return $returnValue;
    }

    private function getStoresAllowedToReindex(): array
    {
        return $this->indexableStoresProvider->getStores();
    }
}

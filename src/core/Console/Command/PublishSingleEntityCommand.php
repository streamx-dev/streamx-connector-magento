<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Console\Command;

use StreamX\ConnectorCore\Indexer\StoreManager;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Console\Command\AbstractIndexerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Usage: bin/magento streamx:index streamx_product_indexer 1 1
 * Where the arguments are, in the order: indexer name, store ID, entity ID.
 * Example: bin/magento streamx:index streamx_product_indexer 1 123
 */
class PublishSingleEntityCommand extends AbstractIndexerCommand
{
    use StreamxIndexerCommandTraits;

    const DESCRIPTION = 'Sends single entity to StreamX (product, category, attribute,  etc..). Useful tool for testing new data.';

    const INPUT_INDEXER_CODE_ARG = 'index';
    const INPUT_STORE_ARG = 'store';
    const INPUT_ENTITY_ID_ARG = 'id';

    private ?StoreManager $indexerStoreManager = null;
    private ?StoreManagerInterface $storeManager = null;

    public function __construct(ObjectManagerFactory $objectManagerFactory)
    {
        parent::__construct($objectManagerFactory);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('streamx:index')
            ->setDescription(self::DESCRIPTION);

        $this->setDefinition($this->getInputList());

        parent::configure();
    }

    /**
     * Get list of options and arguments for the command
     */
    private function getInputList(): array
    {
        return [
            new InputArgument(
                self::INPUT_INDEXER_CODE_ARG,
                InputArgument::REQUIRED,
                'Indexer code'
            ),
            new InputArgument(
                self::INPUT_STORE_ARG,
                InputArgument::REQUIRED,
                'Store ID or Store Code'
            ),
            new InputArgument(
                self::INPUT_ENTITY_ID_ARG,
                InputArgument::REQUIRED,
                'Entity id'
            ),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initObjectManager();
        $output->setDecorated(true);

        $storeId = $input->getArgument(self::INPUT_STORE_ARG);
        $index = $input->getArgument(self::INPUT_INDEXER_CODE_ARG);
        $id = $input->getArgument(self::INPUT_ENTITY_ID_ARG);

        $store = $this->getStoreManager()->getStore($storeId);
        $this->getIndexerStoreManager()->override([$store]);
        $indexer = $this->getStreamxIndex($index);

        if ($indexer) {
            $message = "\nIndex: " . $indexer->getTitle() .
                "\nStore: " . $store->getName() .
                "\nID: " . $id;
            $output->writeln("<info>Indexing... $message</info>");
            $indexer->reindexRow($id);
            return 0;
        } else {
            $output->writeln("<info>Index with code: $index hasn't been found. </info>");
            return -1;
        }
    }

    private function getStoreManager(): StoreManagerInterface
    {
        if (null === $this->storeManager) {
            $this->storeManager = $this->getObjectManager()->get(StoreManagerInterface::class);
        }

        return $this->storeManager;
    }

    private function getIndexerStoreManager(): StoreManager
    {
        if (null === $this->indexerStoreManager) {
            $this->indexerStoreManager = $this->getObjectManager()->get(StoreManager::class);
        }

        return $this->indexerStoreManager;
    }

    private function initObjectManager(): void
    {
        $this->getObjectManager();
    }
}

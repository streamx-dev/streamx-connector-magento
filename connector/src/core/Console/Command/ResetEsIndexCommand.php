<?php

namespace StreamX\ConnectorCore\Console\Command;

use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Console\Command\AbstractIndexerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// TODO removal candidate
class ResetEsIndexCommand extends AbstractIndexerCommand
{
    const STREAMX_INDEXER_PREFIX = 'StreamX_';

    public function __construct(ObjectManagerFactory $objectManagerFactory)
    {
        parent::__construct($objectManagerFactory);
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('streamx:reset')
            ->setDescription('Resets streamx indices status to invalid');

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->invalidateIndices($output);
    }

    private function invalidateIndices(OutputInterface $output)
    {
        foreach ($this->getIndexers() as $indexer) {
            try {
                $indexer->getState()
                    ->setStatus(\Magento\Framework\Indexer\StateInterface::STATUS_INVALID)
                    ->save();
                $output->writeln($indexer->getTitle() . ' indexer has been invalidated.');
            } catch (LocalizedException $e) {
                //catch exception
                $output->writeln("<error>" . $e->getMessage() . "</error>");
            }
        }
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

            if (substr($indexId, 0, 9) === self::STREAMX_INDEXER_PREFIX) {
                $streamxIndexers[] = $indexer;
            }
        }

        return $streamxIndexers;
    }
}

<?php

namespace StreamX\ConnectorCore\Console\Command;

use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Console\Command\AbstractIndexerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Resets all streamx_.* indexers
 * Usage: bin/magento streamx:reset
 */
class ResetStreamxIndexersCommand extends AbstractIndexerCommand
{
    use StreamxIndexerCommandTraits;

    const DESCRIPTION = 'Resets StreamX indexers status to invalid';

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
            ->setDescription(self::DESCRIPTION);

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getStreamxIndexers() as $indexer) {
            try {
                $indexer->getState()
                    ->setStatus(StateInterface::STATUS_INVALID)
                    ->save();
                $output->writeln($indexer->getTitle() . ' indexer has been invalidated.');
            } catch (LocalizedException $e) {
                $output->writeln("<error>" . $e->getMessage() . "</error>");
                return -1;
            }
        }
        return 0;
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class OptimizationSettingsObserver implements ObserverInterface
{
    private const MIN_BATCH = 1;
    private const DEFAULT_BATCH = 100;
    private const MAX_BATCH = 500;

    private OptimizationSettings $optimizationSettings;
    private ManagerInterface $messageManager;
    private WriterInterface $configWriter;

    public function __construct(
        OptimizationSettings $optimizationSettings,
        ManagerInterface $messageManager,
        WriterInterface $configWriter
    ) {
        $this->optimizationSettings = $optimizationSettings;
        $this->messageManager = $messageManager;
        $this->configWriter = $configWriter;
    }

    // override method from ObserverInterface to validate user entered value for batch indexing size
    public function execute(Observer $observer): void {
        $batchSize = $this->optimizationSettings->getBatchIndexingSize();
        if ($batchSize < self::MIN_BATCH || $batchSize > self::MAX_BATCH) {
            $errorMessage = sprintf(
                'Batch indexing size must be between %s and %s. Setting default value %s for this setting',
                self::MIN_BATCH, self::MAX_BATCH, self::DEFAULT_BATCH
            );
            $this->messageManager->addErrorMessage($errorMessage);
            $this->setDefaultBatchIndexingSize();
        }
    }

    private function setDefaultBatchIndexingSize(): void {
        $path = $this->optimizationSettings->getBatchIndexingSizeConfigFieldPath();
        $this->configWriter->save($path, self::DEFAULT_BATCH);
    }
}

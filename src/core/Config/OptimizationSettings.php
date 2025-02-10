<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;

class OptimizationSettings extends BaseConfigurationManager implements ObserverInterface
{
    private const MIN_BATCH = 1;
    private const DEFAULT_BATCH = 100;
    private const MAX_BATCH = 500;

    private ManagerInterface $messageManager;

    public function __construct(ScopeConfigInterface $scopeConfig, WriterInterface $configWriter, ManagerInterface $messageManager) {
        parent::__construct($scopeConfig, 'optimization_settings', $configWriter);
        $this->messageManager = $messageManager;
    }

    public function shouldPerformStreamxAvailabilityCheck(): bool {
        return $this->getBoolConfigValue('should_perform_streamx_availability_check');
    }

    public function getBatchIndexingSize(): int {
        return $this->getIntConfigValue('batch_indexing_size');
    }

    // override method from ObserverInterface to validate user entered value for batch indexing size
    public function execute(Observer $observer): void {
        $batchSize = $this->getBatchIndexingSize();
        if ($batchSize < self::MIN_BATCH || $batchSize > self::MAX_BATCH) {
            $errorMessage = sprintf(
                'Batch indexing size must be between %s and %s. Setting default value %s for this setting',
                self::MIN_BATCH, self::MAX_BATCH, self::DEFAULT_BATCH
            );
            $this->messageManager->addErrorMessage($errorMessage);
            $this->setConfigValue('batch_indexing_size', self::DEFAULT_BATCH);
        }
    }
}

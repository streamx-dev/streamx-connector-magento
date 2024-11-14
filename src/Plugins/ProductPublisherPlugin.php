<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins;

use Closure;
use Magento\Catalog\Controller\Adminhtml\Product\Edit;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class ProductPublisherPlugin {

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function aroundExecute(Edit $subject, Closure $proceed) {
        $this->logger->info('Before admin has edited a product: ' . json_encode((array) $subject));

        $result = $proceed();

        $this->logger->info('After admin has edited a product.');

        // the above logs should be written to: /var/www/html/var/log/system.log

        return $result;
    }
}
<?php declare(strict_types=1);

namespace Streamx\Connector\Model\Indexer;

use Magento\Framework\Mview\ActionInterface;
use Psr\Log\LoggerInterface;

class ProductMviewHandler implements ActionInterface {

    private LoggerInterface $logger;
    private ProductIndexer $productIndexer;

    public function __construct(LoggerInterface $logger, ProductIndexer $productIndexer) {
        $this->logger = $logger;
        $this->productIndexer = $productIndexer;
    }

    // @Override
    public function execute($ids) {
        $this->logInfo("execute(" . json_encode($ids) . ")");
        $this->productIndexer->executeList($ids);
    }

    private function logInfo(string $msg) {
        $date = date("Y-m-d H:i:s");
        $this->logger->info("$date ProductMviewHandler $msg");
    }
}
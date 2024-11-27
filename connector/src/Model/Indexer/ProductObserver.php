<?php declare(strict_types=1);

namespace Streamx\Connector\Model\Indexer;

use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Indexer\IndexerInterface;
use Psr\Log\LoggerInterface;

class ProductObserver {

    private LoggerInterface $logger;
    private ProductIndexer $productIndexer;

    public function __construct(LoggerInterface $logger, ProductIndexer $productIndexer) {
        $this->logger = $logger;
        $this->productIndexer = $productIndexer;
    }

    public function afterSave(ProductResource $productResource, ProductResource $result, ProductModel $product) {
        $this->logInfo('afterSave ' . $product->getId());

        $productResource->addCommitCallback(function () use ($product) {
            if ($this->productIndexer->isUpdateOnSave()) {
                $this->productIndexer->executeRow($product->getId());
            }
        });

        return $result;
    }

    public function afterDelete(ProductResource $productResource, ProductResource $result, ProductModel $product) {
        $this->logInfo('afterDelete ' . $product->getId());

        $productResource->addCommitCallback(function () use ($product) {
            if ($this->productIndexer->isUpdateOnSave()) {
                $this->productIndexer->executeRow($product->getId());
            }
        });

        return $result;
    }

    public function afterUpdateAttributes(Action $subject, Action $result = null, $productIds) {
        $this->logInfo('afterUpdateAttributes ' . json_encode($productIds));

        if ($this->productIndexer->isUpdateOnSave()) {
            $this->productIndexer->executeList($productIds);
        }

        return $result;
    }

    public function afterUpdateWebsites(Action $subject, Action $result = null, array $productIds) {
        $this->logInfo('afterUpdateWebsites ' . json_encode($productIds));

        if ($this->productIndexer->isUpdateOnSave()) {
            $this->productIndexer->executeList($productIds);
        }

        return $result;
    }

    private function logInfo(string $msg) {
        $date = date("Y-m-d H:i:s");
        $this->logger->info("$date ProductObserver $msg");
    }

}
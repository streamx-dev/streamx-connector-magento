<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Indexer\ActionInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;

class ProductAction implements ActionInterface {

    // TODO: load those two from properties, instead of hard-coding as constants
    public string $ingestionBaseUrl = 'http://rest-ingestion:8080';
    public string $pagesSchemaName = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private LoggerInterface $logger;
    private ProductRepository $productRepository;

    public function __construct(LoggerInterface $logger, ProductRepository $productRepository) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    // @Override
    public function executeRow($id) {
        $this->logger->info("ProductAction executeRow($id)");
        $this->executeList([$id]);
    }

    // @Override
    public function executeList(array $ids) {
        $this->logger->info("ProductAction executeList(" . json_encode($ids) . ")");
        foreach ($ids as $id) {
            $product = $this->productRepository->getById($id);
            $this->publishToStreamX($product);
        }
    }

    // @Override
    public function executeFull() {
        $this->logger->info("ProductAction executeFull(): full reindexing is not implemented");
    }

    private function publishToStreamX(ProductInterface $product) {
        $client = StreamxClientBuilders::create($this->ingestionBaseUrl)->build();
        $publisher = $client->newPublisher("pages", $this->pagesSchemaName);
        $page = [
            'content' => [
                'bytes' => sprintf("Admin has edited a Product at %s. Edited state:\n%s",
                    date("Y-m-d H:i:s"),
                    self::toJson($product)
                )
            ]
        ];
        $key = sprintf('product_%s', $product->getId());
        $publisher->publish($key, $page);
    }

    private static function toJson($obj): string {
        return json_encode((array) $obj, JSON_PRETTY_PRINT);
    }
}
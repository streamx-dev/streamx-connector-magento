<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins;

use Closure;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Edit;
use Magento\Catalog\Model\ProductRepository;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;

class ProductPublisherPlugin {

    // TODO: load those two from properties, instead of hard-coding as constants
    public string $ingestionBaseUrl = 'http://rest-ingestion:8080';
    public string $pagesSchemaName = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private LoggerInterface $logger;
    private ProductRepository $productRepository;

    // constructor for magento dependency injection
    public function __construct(LoggerInterface $logger, ProductRepository $productRepository) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
    }

    public function aroundExecute(Edit $subject, Closure $proceed) {
        $this->logger->info('Before admin has edited a product: ' . self::toJson($subject));
        $result = $proceed();
        $this->logger->info('After admin has edited a product.');
        // the above logs should be written to: /var/www/html/var/log/system.log

        $productId = $subject->getRequest()->getParam('id');
        $product = $this->productRepository->getById($productId);
        $this->publishToStreamX($product);

        return $result;
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
        $publisher->publish('key-from-magento-connector', $page);
    }

    private static function toJson($obj): string {
        return json_encode((array) $obj, JSON_PRETTY_PRINT);
    }
}
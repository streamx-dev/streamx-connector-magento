<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins;

use Closure;
use Magento\Catalog\Controller\Adminhtml\Product\Edit;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;

class ProductPublisherPlugin {

    // TODO: load those two from properties, instead of hard-coding as constants
    public string $ingestionBaseUrl = 'http://rest-ingestion:8080';
    public string $pagesSchemaName = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private LoggerInterface $logger;

    // constructor for magento dependency injection
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function aroundExecute(Edit $subject, Closure $proceed) {
        $this->logger->info('Before admin has edited a product: ' . json_encode((array) $subject));
        $result = $proceed();
        $this->logger->info('After admin has edited a product.');
        // the above logs should be written to: /var/www/html/var/log/system.log

        $this->publishToStreamX($subject);

        return $result;
    }

    private function publishToStreamX(Edit $subject) {
        $client = StreamxClientBuilders::create($this->ingestionBaseUrl)->build();
        $publisher = $client->newPublisher("pages", $this->pagesSchemaName);
        $page = ['content' => ['bytes' => 'Admin has edited a Product at ' . date("Y-m-d H:i:s")]];
        $publisher->publish('key-from-magento-connector', $page);
    }
}
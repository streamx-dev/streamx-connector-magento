<?php declare(strict_types=1);


use Magento\Catalog\Api\Data\ProductInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;

class StreamxPublisher {

    // TODO: load those settings from properties/configuration, instead of hard-coding as constants
    public string $ingestionBaseUrl = 'http://rest-ingestion:8080';
    public string $pagesSchemaName = 'dev.streamx.blueprints.data.PageIngestionMessage';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function publishToStreamX(ProductInterface $product) {
        $this->logInfo("Publishing product " . $product->getId());

        if (true) {
            $this->logInfo("publishing to StreamX is turned off for now"); // TODO restore, along with adding a script to add rest-ingestion to magento network
            return;
        }

        // TODO: can client and publisher be created only once (as instance fields) and reused?
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

    private function logInfo(string $msg) {
        $date = date("Y-m-d H:i:s");
        $this->logger->info("$date StreamxPublisher $msg");
    }
}
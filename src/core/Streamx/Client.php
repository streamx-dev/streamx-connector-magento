<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Streamx\Model\Data;
use function Amp\asyncCall;

class Client {

    private LoggerInterface $logger;
    private Publisher $publisher;
    private string $productKeyPrefix;
    private string $categoryKeyPrefix;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        string $productKeyPrefix,
        string $categoryKeyPrefix
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->productKeyPrefix = $productKeyPrefix;
        $this->categoryKeyPrefix = $categoryKeyPrefix;
    }

    public function publish(array $entities, string $indexerName): void {
        $publishMessages = [];
        foreach ($entities as $entity) {
            $key = self::createStreamxKey($indexerName, $entity['id']);
            $payload = new Data(json_encode($entity));
            $publishMessages[] = Message::newPublishMessage($key, $payload)->build();
        }

        $this->ingestAsync($publishMessages, $indexerName, 'Publish');
    }

    public function unpublish(array $entityIds, string $indexerName): void {
        $unpublishMessages = [];
        foreach ($entityIds as $entityId) {
            $key = self::createStreamxKey($indexerName, $entityId);
            $unpublishMessages[] = Message::newUnpublishMessage($key)->build();
        }

        $this->ingestAsync($unpublishMessages, $indexerName, 'Unpublish');
    }

    private function createStreamxKey(string $indexerName, int $entityId): string {
        if ($indexerName == ProductProcessor::INDEXER_ID) {
            return $this->productKeyPrefix . $entityId;
        }
        if ($indexerName == CategoryProcessor::INDEXER_ID) {
            return $this->categoryKeyPrefix . $entityId;
        }
        throw new Exception("Received data from unexpected indexer: $indexerName");
    }

    private function ingestAsync(array $messages, string $indexerName, string $operationName): void {
        $messagesCount = count($messages);
        $this->logger->info("Before calling async $operationName of $messagesCount messages from $indexerName");
        asyncCall(function () use ($messages, $indexerName, $operationName, $messagesCount): \Generator {
            $this->logger->info("Start async $operationName of $messagesCount messages from $indexerName");
            $this->ingest($messages);
            $this->logger->info("Finished async $operationName of $messagesCount messages from $indexerName");
            yield 1;
        });
        $this->logger->info("After calling async $operationName of $messagesCount messages from $indexerName");
    }

    /**
     * @param Message[] $messages Ingestion messages
     */
    private function ingest(array $messages): void {
        try {
            $this->simulateDelay();
            $messageStatuses = $this->publisher->sendMulti($messages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Ingestion exception: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function isStreamxAvailable(): bool {
        try {
            $schema = $this->publisher->fetchSchema();
            if (str_contains($schema, 'IngestionMessage')) {
                return true;
            }
            $this->logger->error("Requested StreamX channel is not available, Ingestion Message definition is missing in schema:\n$schema");
        } catch (Exception $e) {
            $this->logger->error('Exception checking if StreamX is available: ' . $e->getMessage(), ['exception' => $e]);
        }
        return false;
    }

    private function simulateDelay(): void {
        $start = microtime(true);
        $limit = 10000000;
        $result = 0;

        for ($i = 0; $i < $limit; $i++) {
            $result += sqrt($i) * log($i + 1);
        }

        $end = microtime(true);
        $this->logger->info("Operation took " . ($end - $start) . " seconds.");
    }
}

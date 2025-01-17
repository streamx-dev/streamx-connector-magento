<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Streamx\Model\Data;

class Client implements ClientInterface {

    private LoggerInterface $logger;
    private Publisher $publisher;

    public function __construct(LoggerInterface $logger, Publisher $publisher) {
        $this->logger = $logger;
        $this->publisher = $publisher;
    }

    public function ingest(array $bulkOperations): void {
        $operationsCount = count($bulkOperations);
        $this->logger->info("Start ingesting $operationsCount operations");

        $ingestionMessages = array_map(
            function (array $item) {
                return self::mapToIngestionMessage($item);
            },
            $bulkOperations
        );

        if (!empty($ingestionMessages)) {
            $this->ingestToStreamX($ingestionMessages);
        }
        $this->logger->info("Finished ingesting $operationsCount operations");
    }

    private static function mapToIngestionMessage(array $item): Message {
        if (isset($item['publish'])) {
            return self::createPublishMessage($item['publish']);
        }
        if (isset($item['unpublish'])) {
            return self::createUnpublishMessage($item['unpublish']);
        }
        throw new Exception('Unexpected bulk item type: ' . json_encode($item, JSON_PRETTY_PRINT));
    }

    private static function createPublishMessage(array $publishItem): Message {
        $entityType = $publishItem['type'];
        $entity = $publishItem['entity'];
        $entityId = $entity['id'];
        $key = self::createStreamxKey($entityType, $entityId);
        $payload = new Data(json_encode($entity));
        return Message::newPublishMessage($key, $payload)->build();
    }

    private static function createUnpublishMessage(array $unpublishItem): Message {
        $entityType = $unpublishItem['type'];
        $entityId = $unpublishItem['id'];
        $key = self::createStreamxKey($entityType, $entityId);
        return Message::newUnpublishMessage($key)->build();
    }

    private static function createStreamxKey($entityType, $entityId): string {
        switch ($entityType) {
            case 'product':
                return "pim:$entityId"; // TODO make prefixes configurable
            case 'category':
                return "cat:$entityId";
            case 'attribute':
                return "attr:$entityId";
            default:
                throw new Exception("Unexpected entity type: $entityType");
        }
    }

    /**
     * @param Message[] $messages Ingestion messages
     */
    private function ingestToStreamX(array $messages): void {
        try {
            // TODO make sure this will never block. Best by turning off Pulsar container
            $messageStatuses = $this->publisher->sendMulti($messages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (StreamxClientException $e) {
            $this->logger->error('Ingestion exception: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function isStreamxAvailable(): bool {
        try {
            return $this->publisher->isIngestionServiceAvailable();
        } catch (Exception $e) {
            $this->logger->error('Exception checking if StreamX is available: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
}

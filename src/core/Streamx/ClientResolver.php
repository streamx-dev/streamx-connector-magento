<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

class ClientResolver {
    private array $clients = [];

    private LoggerInterface $logger;
    private ClientConfiguration $configuration;
    private StreamxPublisherProvider $streamxPublisherProvider;

    public function __construct(
        LoggerInterface $logger,
        ClientConfiguration $configuration,
        StreamxPublisherProvider $streamxPublisherProvider
    ) {
        $this->logger = $logger;
        $this->configuration = $configuration;
        $this->streamxPublisherProvider = $streamxPublisherProvider;
    }

    /**
     * @throws StreamxClientException
     */
    public function getClient(int $storeId): Client {
        if (!isset($this->clients[$storeId])) {
            $publisher = $this->streamxPublisherProvider->getStreamxPublisher(
                $this->configuration->getIngestionBaseUrl($storeId),
                $this->configuration->getChannelName($storeId),
                $this->configuration->getChannelSchemaName($storeId),
                $this->configuration->getAuthToken($storeId),
                $this->configuration->shouldDisableCertificateValidation($storeId)
            );

            $this->clients[$storeId] = new Client(
                $this->logger,
                $publisher,
                $this->configuration->getProductKeyPrefix($storeId),
                $this->configuration->getCategoryKeyPrefix($storeId)
            );
        } else {
            $this->logger->info("Reusing StreamX client and publisher");
        }

        return $this->clients[$storeId];
    }
}

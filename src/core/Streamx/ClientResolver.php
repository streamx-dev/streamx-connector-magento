<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Api\Client\ConfigurationInterface;
use StreamX\ConnectorCore\Api\Client\ConfigurationInterfaceFactory;

class ClientResolver {
    private array $clients = [];

    private LoggerInterface $logger;
    private ConfigurationInterfaceFactory $clientConfigurationFactory;
    private StreamxPublisherProvider $streamxPublisherProvider;

    public function __construct(
        LoggerInterface               $logger,
        ConfigurationInterfaceFactory $clientConfiguration,
        StreamxPublisherProvider      $streamxPublisherProvider
    ) {
        $this->logger = $logger;
        $this->clientConfigurationFactory = $clientConfiguration;
        $this->streamxPublisherProvider = $streamxPublisherProvider;
    }

    /**
     * @throws StreamxClientException
     */
    public function getClient(int $storeId): ClientInterface {
        if (!isset($this->clients[$storeId])) {
            /** @var ConfigurationInterface $configuration */
            $configuration = $this->clientConfigurationFactory->create(['storeId' => $storeId]);

            $publisher = $this->streamxPublisherProvider->getStreamxPublisher(
                $configuration->getIngestionBaseUrl($storeId),
                $configuration->getChannelName($storeId),
                $configuration->getChannelSchemaName($storeId),
                $configuration->getAuthToken($storeId)
            );

            $this->clients[$storeId] = new Client(
                $this->logger,
                $publisher,
                $configuration->getProductKeyPrefix($storeId),
                $configuration->getCategoryKeyPrefix($storeId)
            );
        } else {
            $this->logger->info("Reusing StreamX client and publisher");
        }

        return $this->clients[$storeId];
    }
}

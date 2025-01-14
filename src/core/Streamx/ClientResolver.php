<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Api\Client\ClientInterfaceFactory;
use StreamX\ConnectorCore\Api\Client\ConfigurationInterface;
use StreamX\ConnectorCore\Api\Client\ConfigurationInterfaceFactory;
use StreamX\ConnectorCore\System\GeneralConfigInterface;

class ClientResolver {
    private array $clients = [];

    private GeneralConfigInterface $config;
    private ClientInterfaceFactory $clientFactory;
    private ConfigurationInterfaceFactory $clientConfigurationFactory;
    private StreamxPublisherProvider $streamxPublisherProvider;

    public function __construct(
        GeneralConfigInterface        $config,
        ClientInterfaceFactory        $clientFactory,
        ConfigurationInterfaceFactory $clientConfiguration,
        StreamxPublisherProvider      $streamxPublisherProvider) {
        $this->config = $config;
        $this->clientFactory = $clientFactory;
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
            $publisher = $this->streamxPublisherProvider->getStreamxPublisher($configuration->getOptions($storeId));
            $this->clients[$storeId] = $this->clientFactory->create(['publisher' => $publisher]);
        }

        return $this->clients[$storeId];
    }
}

<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Streamx;

use Divante\VsbridgeIndexerCore\Api\Client\ClientInterface;
use Divante\VsbridgeIndexerCore\Api\Client\ClientInterfaceFactory;
use Divante\VsbridgeIndexerCore\Api\Client\ConfigurationInterface;
use Divante\VsbridgeIndexerCore\Api\Client\ConfigurationInterfaceFactory;
use Divante\VsbridgeIndexerCore\Exception\ConnectionDisabledException;
use Divante\VsbridgeIndexerCore\System\GeneralConfigInterface;

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

    public function getClient(int $storeId): ClientInterface {
        if (!$this->config->isEnabled()) {
            throw new ConnectionDisabledException('StreamX indexer is disabled.');
        }

        if (!isset($this->clients[$storeId])) {
            /** @var ConfigurationInterface $configuration */
            $configuration = $this->clientConfigurationFactory->create(['storeId' => $storeId]);
            $publisher = $this->streamxPublisherProvider->buildPublisher($configuration->getOptions($storeId));
            $this->clients[$storeId] = $this->clientFactory->create(['publisher' => $publisher]);
        }

        return $this->clients[$storeId];
    }
}

<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Streamx;

use Divante\VsbridgeIndexerCore\Api\Client\BuilderInterface as ClientBuilder;
use Divante\VsbridgeIndexerCore\Api\Client\ClientInterface;
use Divante\VsbridgeIndexerCore\Api\Client\ClientInterfaceFactory;
use Divante\VsbridgeIndexerCore\Api\Client\ConfigurationInterface;
use Divante\VsbridgeIndexerCore\Api\Client\ConfigurationInterfaceFactory;
use Divante\VsbridgeIndexerCore\Exception\ConnectionDisabledException;
use Divante\VsbridgeIndexerCore\System\GeneralConfigInterface;

class ClientResolver {
    private array $clients = [];

    private GeneralConfigInterface $config;

    private ClientBuilder $clientBuilder;

    private ConfigurationInterfaceFactory $clientConfigurationFactory;

    private ClientInterfaceFactory $clientFactory;

    /**
     * ClientResolver constructor.
     *
     * @param GeneralConfigInterface $config
     * @param ClientBuilder $clientBuilder
     * @param ClientInterfaceFactory $clientFactory
     * @param ConfigurationInterfaceFactory $clientConfiguration
     */
    public function __construct(
        GeneralConfigInterface        $config,
        ClientBuilder                 $clientBuilder,
        ClientInterfaceFactory        $clientFactory,
        ConfigurationInterfaceFactory $clientConfiguration) {
        $this->config = $config;
        $this->clientFactory = $clientFactory;
        $this->clientBuilder = $clientBuilder;
        $this->clientConfigurationFactory = $clientConfiguration;
    }

    public function getClient(int $storeId): ClientInterface {
        if (!$this->config->isEnabled()) {
            throw new ConnectionDisabledException('StreamX indexer is disabled.');
        }

        if (!isset($this->clients[$storeId])) {
            /** @var ConfigurationInterface $configuration */
            $configuration = $this->clientConfigurationFactory->create(['storeId' => $storeId]);
            $httpClient = $this->clientBuilder->build($configuration->getOptions($storeId));
            $this->clients[$storeId] = $this->clientFactory->create(['client' => $httpClient]);
        }

        return $this->clients[$storeId];
    }
}

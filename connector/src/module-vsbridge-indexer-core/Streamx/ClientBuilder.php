<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Streamx;

use Divante\VsbridgeIndexerCore\Api\Client\BuilderInterface as ClientBuilderInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class ClientBuilder implements ClientBuilderInterface {

    private array $defaultOptions = [
        'host' => 'localhost',
        'port' => '9200',
        'enable_http_auth' => false,
        'auth_user' => null,
        'auth_pwd' => null,
        'timeout' => 30,
        'connect_timeout' => 30
    ];

    private Client $client;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * Creates Guzzle HTTP Client configured to call StreamDX API
     * TODO: It returns Guzzle HTTP client which is not what's defined in the interface.
     * Leaving it for now, as the original implementation missed the type too.
     */
    public function build(array $options = []): Client {
        if (isset($this->client)) {
            $this->logger->debug("Reusing client");
            return $this->client;
        }

        $options = array_merge($this->defaultOptions, $options);
        $this->logger->debug("Creating new client with options: " . json_encode($options));

        $scheme = 'http';
        if (isset($options['enable_https_mode'])) {
            $scheme = 'https';
        } elseif (isset($options['scheme'])) {
            $scheme = $options['scheme'];
        }
        $url = $scheme . "://" . $options['host'] . ":" . $options['port'] . "/alpha1/publications";

        $clientParameters = [
            'base_uri' => $url
        ];
        if ($options['enable_http_auth']) {
            $clientParameters['auth'] = [$options['auth_user'], $options['auth_pwd']];
        }
        $this->client = new Client($clientParameters);
        $this->logger->debug("Returning new client with options: " . json_encode($clientParameters));

        return $this->client;
    }

}

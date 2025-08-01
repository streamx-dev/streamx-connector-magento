<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Streamx\Clients\Ingestion\Publisher\Publisher;

class StreamxPublisher {

    private Publisher $publisher;
    private string $baseUrl;

    public function __construct(Publisher $publisher, string $baseUrl) {
        $this->publisher = $publisher;
        $this->baseUrl = $baseUrl;
    }

    public function getPublisher(): Publisher {
        return $this->publisher;
    }

    public function getBaseUrl(): string {
        return $this->baseUrl;
    }
}
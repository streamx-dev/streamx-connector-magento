<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

class RabbitMqConnectionSettings {

    private string $host;
    private int $port;
    private string $user;
    private string $password;

    public function __construct(string $host, int $port, string $user, string $password) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function getUser(): string {
        return $this->user;
    }

    public function getPassword(): string {
        return $this->password;
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class RabbitMqConfiguration extends BaseConfigurationReader {

    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'rabbit_mq');
    }

    public function isEnabled(): bool {
        return (bool)$this->getGlobalConfigValue('enable');
    }

    public function getHost(): string {
        return (string)$this->getGlobalConfigValue('host');
    }

    public function getPort(): int {
        return (int)$this->getGlobalConfigValue('port');
    }

    public function getUser(): string {
        return (string)$this->getGlobalConfigValue('user');
    }

    public function getPassword(): ?string {
        return $this->getGlobalConfigValue('password');
    }
}

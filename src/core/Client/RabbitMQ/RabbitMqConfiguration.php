<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class RabbitMqConfiguration extends BaseConfigurationReader {

    public function __construct(ScopeConfigInterface $scopeConfig, ResourceConnection $connection) {
        parent::__construct($connection, $scopeConfig, 'rabbit_mq');
    }

    public function isEnabled(): bool {
        return (bool) $this->getGlobalConfigValue('enable');
    }

    public function getConnectionSettings(): RabbitMqConnectionSettings {
        return new RabbitMqConnectionSettings(
            (string) $this->getGlobalConfigValue('host'),
            (int) $this->getGlobalConfigValue('port'),
            (string) $this->getGlobalConfigValue('user'),
            (string) $this->getGlobalConfigValue('password')
        );
    }
}

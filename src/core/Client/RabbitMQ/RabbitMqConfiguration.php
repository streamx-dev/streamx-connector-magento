<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

/**
 * This configuration reader class is special:
 *  - it reads settings from database directly.
 * This makes the class safe to be used in long-running services, where always up-to-date values are required
 */
class RabbitMqConfiguration extends BaseConfigurationReader {

    private ResourceConnection $resource;

    public function __construct(ScopeConfigInterface $scopeConfig, ResourceConnection $resource) {
        parent::__construct($scopeConfig, 'rabbit_mq');
        $this->resource = $resource;
    }

    public function isEnabled(): bool {
        $connection = $this->resource->getConnection();
        return (bool) $this->readConfigValueFromDb('enable', $connection);
    }

    public function getConnectionSettings(): RabbitMqConnectionSettings {
        $connection = $this->resource->getConnection();
        return new RabbitMqConnectionSettings(
            (string) $this->readConfigValueFromDb('host', $connection),
            (int) $this->readConfigValueFromDb('port', $connection),
            (string) $this->readConfigValueFromDb('user', $connection),
            (string) $this->readConfigValueFromDb('password', $connection)
        );
    }

    private function readConfigValueFromDb(string $configField, AdapterInterface $connection) {
        $path = parent::getConfigFieldFullPath($configField);
        $value = $connection->fetchOne('
            SELECT value
              FROM core_config_data
             WHERE path = ?
        ', $path);

        return $value === false // fetchOne returns false if no matching rows
            ? parent::getGlobalConfigValue($configField)
            : $value;
    }
}

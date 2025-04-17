<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
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
        return (bool) $this->getGlobalConfigValue('enable');
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

    public function getPassword(): string {
        return (string)$this->getGlobalConfigValue('password');
    }

    /**
     * @inheritdoc
     */
    protected function getGlobalConfigValue(string $configField) {
        $path = parent::getConfigFieldFullPath($configField);
        $value = $this->resource->getConnection()->fetchOne('
            SELECT value
              FROM core_config_data
             WHERE path = ?
        ', $path);

        return $value === false // fetchOne returns false if no matching rows
            ? parent::getGlobalConfigValue($configField)
            : $value;
    }
}

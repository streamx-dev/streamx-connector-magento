<?php

namespace StreamX\ConnectorCore\Index\Indicies\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;

class SchemaLocator implements SchemaLocatorInterface
{
    /**
     * XML schema for config file.
     */
    const CONFIG_FILE_SCHEMA = 'streamx_indices.xsd';

    /**
     * Path to corresponding XSD file with validation rules for merged config
     */
    private string $schema;

    /**
     * Path to corresponding XSD file with validation rules for separate config files
     */
    private string $perFileSchema;

    public function __construct(\Magento\Framework\Module\Dir\Reader $moduleReader)
    {
        $configDir = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'StreamX_ConnectorCore');
        $this->schema = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_FILE_SCHEMA;
        $this->perFileSchema = $configDir . DIRECTORY_SEPARATOR . self::CONFIG_FILE_SCHEMA;
    }

    /**
     * @inheritdoc
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @inheritdoc
     */
    public function getPerFileSchema()
    {
        return $this->perFileSchema;
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Error
 */
class Error extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::ERROR;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/vsbridge-indexer/catalog_error.log';
}

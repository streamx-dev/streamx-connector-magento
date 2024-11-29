<?php

namespace Divante\VsbridgeIndexerCore\Cache\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Info extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/vsbridge-indexer/cache.log';
}

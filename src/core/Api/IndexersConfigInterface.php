<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use StreamX\ConnectorCore\Api\Index\TypeInterface;

interface IndexersConfigInterface
{
    public function getByName(string $indexerName): TypeInterface;
}
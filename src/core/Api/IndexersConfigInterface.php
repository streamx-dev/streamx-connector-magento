<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use StreamX\ConnectorCore\Index\IndexerDefinition;

interface IndexersConfigInterface
{
    public function getByName(string $indexerName): IndexerDefinition;
}
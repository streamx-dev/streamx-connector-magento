<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Api;

interface LoadMediaGalleryInterface
{
    /**
     * @throws \Exception
     */
    public function execute(array $indexData, int $storeId): array;
}

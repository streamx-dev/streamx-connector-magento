<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api;

interface LoadMediaGalleryInterface
{
    /**
     * @throws \Exception
     */
    public function execute(array $indexData, int $storeId): array;
}

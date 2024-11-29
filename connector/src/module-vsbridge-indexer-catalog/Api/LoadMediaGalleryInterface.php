<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api;

/**
 * Interface LoadMediaGalleryInterface
 */
interface LoadMediaGalleryInterface
{
    /**
     * @param array $indexData
     * @param int $storeId
     *
     * @return array
     * @throws \Exception
     */
    public function execute(array $indexData, int $storeId): array;
}

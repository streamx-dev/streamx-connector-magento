<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class ImageUrlManager {

    private StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager) {
        $this->storeManager = $storeManager;
    }

    public function getProductImageUrl(string $imageRelativePath, int $storeId): string {
        $store = $this->storeManager->getStore($storeId);
        $storeBaseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_LINK, true);
        return implode('/', [
            rtrim($storeBaseUrl, '/'),
            'media/catalog/product',
            ltrim($imageRelativePath, '/')
        ]);
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Store\Model\StoreManagerInterface;

class ImageUrlManager {

    private string $productImagesBaseUrl;

    public function __construct(StoreManagerInterface $storeManager) {
        $storeBaseUrl = $storeManager->getStore()->getBaseUrl();
        $this->productImagesBaseUrl = self::joinUrlParts($storeBaseUrl, '/media/catalog/product/');
    }

    public function getProductImageUrl(string $imageRelativePath): string {
        return self::joinUrlParts($this->productImagesBaseUrl, $imageRelativePath);
    }

    private static function joinUrlParts(string $part1, string $part2): string {
        return $part1 . self::removeLeadingSlash($part2);
    }

    private static function removeLeadingSlash(string $path): string {
        return ltrim($path, '/');
    }
}

<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Rewrite as RewriteResource;

/**
 * Class ProductUrlPathGenerator
 */
class ProductUrlPathGenerator
{
    /**
     * @var RewriteResource
     */
    private $rewriteResource;

    /**
     * ProductUrlPathGenerator constructor.
     *
     * @param RewriteResource $rewrite
     */
    public function __construct(RewriteResource $rewrite)
    {
        $this->rewriteResource = $rewrite;
    }

    /**
     * Add URL path
     *
     * @param array $products
     * @param int $storeId
     *
     * @return array
     */
    public function addUrlPath(array $products, $storeId): array
    {
        $productIds = array_keys($products);
        $rewrites = $this->rewriteResource->getRawRewritesData($productIds, $storeId);

        foreach ($rewrites as $productId => $rewrite) {
            $products[$productId]['url_path'] = $rewrite;
        }

        return $products;
    }
}

<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Rewrite as RewriteResource;

class ProductUrlPathGenerator
{
    /**
     * @var RewriteResource
     */
    private $rewriteResource;

    public function __construct(RewriteResource $rewrite)
    {
        $this->rewriteResource = $rewrite;
    }

    /**
     * Add URL path
     *
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

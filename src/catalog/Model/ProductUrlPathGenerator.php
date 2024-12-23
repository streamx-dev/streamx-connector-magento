<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Rewrite as RewriteResource;

class ProductUrlPathGenerator
{
    private RewriteResource $rewriteResource;

    public function __construct(RewriteResource $rewrite)
    {
        $this->rewriteResource = $rewrite;
    }

    public function addUrlPath(array $products, int $storeId): array
    {
        $productIds = array_keys($products);
        $rewrites = $this->rewriteResource->getRawRewritesData($productIds, $storeId);

        foreach ($rewrites as $productId => $rewrite) {
            $products[$productId]['url_path'] = $rewrite;
        }

        return $products;
    }
}

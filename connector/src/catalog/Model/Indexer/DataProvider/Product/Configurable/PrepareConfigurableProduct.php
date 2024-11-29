<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable;

class PrepareConfigurableProduct
{
    public function execute(array $productDTO): array
    {
        $configurableChildren = $productDTO['configurable_children'];
        $areChildInStock = 0;
        $specialPrice = $finalPrice = $childPrice = [];

        foreach ($configurableChildren as $child) {
            if (!empty($child['stock']['is_in_stock'])) {
                $areChildInStock = 1;
            }

            if (isset($child['special_price'])) {
                $specialPrice[] = $child['special_price'];
            }

            if (isset($child['price'])) {
                $childPrice[] = $child['price'];
                $finalPrice[] = $child['final_price'] ?? $child['price'];
            }
        }

        $productDTO['final_price'] = !empty($finalPrice) ? min($finalPrice): null;
        $productDTO['special_price'] = !empty($specialPrice) ? min($specialPrice) : null;
        $productDTO['price'] = !empty($childPrice) ? min($childPrice): null;
        $productDTO['regular_price'] = $productDTO['price'];


        if (empty($productDTO['stock']['is_in_stock']) || !$areChildInStock) {
            $productDTO['stock']['is_in_stock'] = false;
            $productDTO['stock']['stock_status'] = 0;
        }

        return $productDTO;
    }
}

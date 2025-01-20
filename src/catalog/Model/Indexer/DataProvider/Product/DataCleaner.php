<?php

declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;

/**
 * Use this Data Provider to remove any data added or required by previous providers
 * that is not necessary in the final product to be published.
 */
class DataCleaner implements DataProviderInterface
{

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        foreach ($indexData as &$product) {
            unset($product['type_id'], $product['url_key']);
        }

        return $indexData;
    }
}

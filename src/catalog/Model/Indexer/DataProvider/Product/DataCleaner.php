<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;

/**
 * Use this Data Provider to remove any data added or required by previous providers
 * that is not necessary in the final product to be published.
 */
class DataCleaner implements DataProviderInterface
{
    protected const FIELDS_TO_REMOVE = [
        'entity_id',
        'row_id',
        'type_id',
        'url_key'
    ];

    /**
     * @inheritdoc
     */
    public function addData(array &$indexData, int $storeId): void
    {
        foreach ($indexData as &$product) {
            foreach (static::FIELDS_TO_REMOVE as $field) {
                unset($product[$field]);
            }
        }
    }
}

<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;

/**
 * Use this Data Provider as the last Data Provider, to adjust the produced data to match the required schema
 */
class DataCleaner implements DataProviderInterface
{
    protected const FIELDS_TO_REMOVE = [
        'entity_id',
        'row_id',
        'type_id'
    ];

    /**
     * @inheritdoc
     */
    public function addData(array &$indexData, int $storeId): void {
        $this->changeFieldTypes($indexData);
        $this->removeUnnecessaryFields($indexData);
    }

    private function changeFieldTypes(array &$indexData): void {
        foreach ($indexData as &$product) {
            $product['id'] = (string) $product['id'];
        }
    }

    private function removeUnnecessaryFields(array &$indexData): void {
        foreach ($indexData as &$product) {
            foreach (static::FIELDS_TO_REMOVE as $field) {
                unset($product[$field]);
            }
        }
    }
}

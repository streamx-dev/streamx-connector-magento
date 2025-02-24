<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;

/**
 * Use this Data Provider as the last Data Provider, to adjust the produced data to match the required schema
 */
class DataCleaner extends DataProviderInterface
{

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $this->changeFieldTypes($indexData);
        $this->removeUnnecessaryFields($indexData);

        return $indexData; // TODO change DataProviderInterface to modify data inline
    }

    private function changeFieldTypes(array &$indexData): void {
        foreach ($indexData as &$product) {
            $product['id'] = (string) $product['id'];
        }
    }

    private function removeUnnecessaryFields(array &$indexData): void {
        foreach ($indexData as &$product) {
            unset(
                $product['entity_id'],
                $product['row_id'],
                $product['type_id'],
                $product['url_key']
            );
        }
    }
}

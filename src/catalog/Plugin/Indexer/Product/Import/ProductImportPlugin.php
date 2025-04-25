<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Product\Import;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\ProductMetaData;

/**
 * Intercepts import of products from a csv file (added and changed products) and publishes them to StreamX.
 * Deleted products are already gone from DB at this moment, so are handled at earlier stage: by ProductDeletedViaImportObserver.
 *
 * Note: no similar plugin for Category Import, because while importing products from csv file,
 * Magento internally creates all the categories found in the file,
 * and calls standard category.save() on them, triggering our dedicated plugin that detects categories changes.
 */
class ProductImportPlugin {

    private LoggerInterface $logger;
    private ProductProcessor $productProcessor;
    private ResourceConnection $resource;
    private ProductMetaData $productMetaData;

    public function __construct(
        LoggerInterface    $logger,
        ProductProcessor   $productProcessor,
        ResourceConnection $resource,
        ProductMetaData    $productMetaData
    ) {
        $this->logger = $logger;
        $this->productProcessor = $productProcessor;
        $this->resource = $resource;
        $this->productMetaData = $productMetaData;
    }

    public function afterImportData(Product $subject, bool $result): bool {
        if ($this->productProcessor->isIndexerScheduled()) {
            // do nothing if the indexer is currently in Update By Schedule mode - mView should collect the product IDs into streamx_product_indexer_cl table
            return $result;
        }

        $importRowIds = $subject->getIds();
        if (empty($importRowIds)) {
            return $result;
        }

        $productJsonsFromImportFiles = self::readJsonsOfProductsToImport($importRowIds);
        if (empty($productJsonsFromImportFiles)) {
            return $result;
        }

        $skus = self::extractSkus($productJsonsFromImportFiles);
        if (empty($skus)) {
            return $result;
        }

        $productIds = self::getProductIdsFromSkus($skus);
        if (empty($productIds)) {
            return $result;
        }

        $this->logger->info('Reindexing imported products, IDs: ' . json_encode($productIds));
        $this->productProcessor->reindexList($productIds);

        return $result;
    }

    private function readJsonsOfProductsToImport(array $importRowIds): array {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from('importexport_importdata', ['data'])
            ->where('id IN (?)', $importRowIds);
        return $connection->fetchCol($select);
    }

    private function extractSkus(array $productJsonsFromImportFiles): array {
        $skus = [];
        foreach ($productJsonsFromImportFiles as $productJsonsFromSingleImportFile) {
            $productsData = json_decode($productJsonsFromSingleImportFile, true);
            foreach ($productsData as $productData) {
                $skus[] = $productData['sku'];
            }
        }
        return $skus;
    }

    private function getProductIdsFromSkus(array $skus): array {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->productMetaData->getEntityTable(), ['entity_id'])
            ->where('sku IN (?)', $skus);
        return array_map('intval', $connection->fetchCol($select));
    }
}
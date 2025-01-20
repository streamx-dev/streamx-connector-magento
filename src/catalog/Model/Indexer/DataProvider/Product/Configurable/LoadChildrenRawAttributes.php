<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Model\Attributes\ConfigurableAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Prices as PriceResourceModel;
use StreamX\ConnectorCatalog\Model\Product\LoadTierPrices;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\Product\LoadMediaGallery;
use Traversable;

class LoadChildrenRawAttributes
{
    private LoadTierPrices $loadTierPrices;
    private PriceResourceModel $priceResourceModel;
    private ProductAttributesProvider $resourceAttributeModel;
    private ConfigurableAttributes $configurableAttributes;
    private LoadMediaGallery $loadMediaGallery;
    private CatalogConfig $settings;

    public function __construct(
        CatalogConfig $catalogConfiguration,
        ProductAttributesProvider $attributeDataProvider,
        ConfigurableAttributes $configurableAttributes,
        LoadTierPrices $loadTierPrices,
        LoadMediaGallery $loadMediaGallery,
        PriceResourceModel $priceResourceModel
    ) {
        $this->settings = $catalogConfiguration;
        $this->loadTierPrices = $loadTierPrices;
        $this->loadMediaGallery = $loadMediaGallery;
        $this->priceResourceModel = $priceResourceModel;
        $this->resourceAttributeModel = $attributeDataProvider;
        $this->configurableAttributes = $configurableAttributes;
    }

    /**
     * @throws Exception
     * @throws LocalizedException
     */
    public function execute(int $storeId, array $allChildren, array $configurableAttributeCodes): array
    {
        $requiredAttributes = $this->getRequiredChildrenAttributes($storeId);

        if (!empty($requiredAttributes)) {
            $requiredAttributes = array_merge(
                $requiredAttributes,
                $configurableAttributeCodes
            );
        }

        $requiredAttribute = array_unique($requiredAttributes);

        foreach ($this->getChildrenInBatches($allChildren, $storeId) as $batch) {
            $childIds = array_keys($batch);
            $priceData = $this->priceResourceModel->loadPriceData($storeId, $childIds);

            $allAttributesData = $this->resourceAttributeModel->loadAttributesData(
                $storeId,
                $childIds,
                $requiredAttribute
            );

            foreach ($priceData as $childId => $priceDataRow) {
                $allChildren[$childId]['final_price'] = (float)$priceDataRow['final_price'];

                if (isset($priceDataRow['price'])) {
                    $allChildren[$childId]['regular_price'] = (float)$priceDataRow['price'];
                }
            }

            foreach ($allAttributesData as $productId => $attributes) {
                $newProductData = array_merge(
                    $allChildren[$productId],
                    $attributes
                );

                if (
                    $this->settings->syncTierPrices() ||
                    $this->configurableAttributes->canIndexMediaGallery($storeId)
                ) {
                    /*we need some extra attributes to apply tier prices*/
                    $batch[$productId] = $newProductData;
                } else {
                    $allChildren[$productId] = $newProductData;
                }
            }

            $replace = false;

            if ($this->settings->syncTierPrices()) {
                $batch = $this->loadTierPrices->execute($batch, $storeId);
                $replace = true;
            }

            if ($this->configurableAttributes->canIndexMediaGallery($storeId)) {
                $batch = $this->loadMediaGallery->execute($batch, $storeId);
                $replace = true;
            }

            if ($replace) {
                $allChildren = array_replace_recursive($allChildren, $batch);
            }
        }

        return $allChildren;
    }

    private function getRequiredChildrenAttributes(int $storeId): array
    {
        return $this->configurableAttributes->getChildrenRequiredAttributes($storeId);
    }

    private function getChildrenInBatches(array $documents, int $storeId): Traversable
    {
        $batchSize = $this->getBatchSize($storeId);
        $i = 0;
        $batch = [];

        foreach ($documents as $documentName => $documentValue) {
            $batch[$documentName] = $documentValue;

            if (++$i == $batchSize) {
                yield $batch;
                $i = 0;
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            yield $batch;
        }
    }

    private function getBatchSize(int $storeId): int
    {
        return $this->settings->getConfigurableChildrenBatchSize($storeId);
    }
}

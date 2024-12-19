<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product\AttributeDataProvider;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Prices as PriceResourceModel;
use StreamX\ConnectorCatalog\Api\LoadTierPricesInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Api\LoadMediaGalleryInterface;
use Traversable;

class LoadChildrenRawAttributes
{
    private LoadTierPricesInterface $loadTierPrices;
    private PriceResourceModel $priceResourceModel;
    private AttributeDataProvider $resourceAttributeModel;
    private LoadMediaGalleryInterface $mediaGalleryLoader;
    private CatalogConfigurationInterface $settings;

    public function __construct(
        CatalogConfigurationInterface $catalogConfiguration,
        AttributeDataProvider $attributeDataProvider,
        LoadTierPricesInterface $loadTierPrices,
        LoadMediaGalleryInterface $loadMediaGallery,
        PriceResourceModel $priceResourceModel
    ) {
        $this->settings = $catalogConfiguration;
        $this->loadTierPrices = $loadTierPrices;
        $this->mediaGalleryLoader = $loadMediaGallery;
        $this->priceResourceModel = $priceResourceModel;
        $this->resourceAttributeModel = $attributeDataProvider;
    }

    /**
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(int $storeId, array $allChildren, array $configurableAttributeCodes): array
    {
        foreach ($this->getChildrenInBatches($allChildren, $storeId) as $batch) {
            $childIds = array_keys($batch);
            $priceData = $this->priceResourceModel->loadPriceData($storeId, $childIds);

            $allAttributesData = $this->resourceAttributeModel->loadAttributesData(
                $storeId,
                $childIds
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

                /*we need some extra attributes to apply tier prices*/
                $batch[$productId] = $newProductData;
            }

            if ($this->settings->syncTierPrices()) {
                $batch = $this->loadTierPrices->execute($batch, $storeId);
            }

            $batch = $this->mediaGalleryLoader->execute($batch, $storeId);

            $allChildren = array_replace_recursive($allChildren, $batch);
        }

        return $allChildren;
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

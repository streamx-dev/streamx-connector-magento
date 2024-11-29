<?php declare(strict_types = 1);

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product\Configurable;

use Divante\VsbridgeIndexerCatalog\Model\Attributes\ConfigurableAttributes;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\AttributeDataProvider;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Prices as PriceResourceModel;
use Divante\VsbridgeIndexerCatalog\Api\LoadTierPricesInterface;
use Divante\VsbridgeIndexerCatalog\Api\CatalogConfigurationInterface;
use Divante\VsbridgeIndexerCatalog\Api\LoadMediaGalleryInterface;

class LoadChildrenRawAttributes
{
    /**
     * @var LoadTierPricesInterface
     */
    private $loadTierPrices;

    /**
     * @var PriceResourceModel
     */
    private $priceResourceModel;

    /**
     * @var  AttributeDataProvider
     */
    private $resourceAttributeModel;

    /**
     * @var ConfigurableAttributes
     */
    private $configurableAttributes;

    /**
     * @var LoadMediaGalleryInterface
     */
    private $mediaGalleryLoader;

    /**
     * @var CatalogConfigurationInterface
     */
    private $settings;

    public function __construct(
        CatalogConfigurationInterface $catalogConfiguration,
        AttributeDataProvider $attributeDataProvider,
        ConfigurableAttributes $configurableAttributes,
        LoadTierPricesInterface $loadTierPrices,
        LoadMediaGalleryInterface $loadMediaGallery,
        PriceResourceModel $priceResourceModel
    ) {
        $this->settings = $catalogConfiguration;
        $this->loadTierPrices = $loadTierPrices;
        $this->mediaGalleryLoader = $loadMediaGallery;
        $this->priceResourceModel = $priceResourceModel;
        $this->resourceAttributeModel = $attributeDataProvider;
        $this->configurableAttributes = $configurableAttributes;
    }

    /**
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
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
                $batch = $this->mediaGalleryLoader->execute($batch, $storeId);
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

    /**
     * @return \Generator
     */
    private function getChildrenInBatches(array $documents, int $storeId)
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

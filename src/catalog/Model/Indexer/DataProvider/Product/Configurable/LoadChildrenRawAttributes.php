<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Model\Attributes\ConfigurableAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use Traversable;

class LoadChildrenRawAttributes
{
    private ProductAttributesProvider $resourceAttributeModel;
    private ConfigurableAttributes $configurableAttributes;
    private CatalogConfig $settings;

    public function __construct(
        CatalogConfig $catalogConfiguration,
        ProductAttributesProvider $attributeDataProvider,
        ConfigurableAttributes $configurableAttributes
    ) {
        $this->settings = $catalogConfiguration;
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

            $allAttributesData = $this->resourceAttributeModel->loadAttributesData(
                $storeId,
                $childIds,
                $requiredAttribute
            );

            foreach ($allAttributesData as $productId => $attributes) {
                $newProductData = array_merge(
                    $allChildren[$productId],
                    $attributes
                );

                $allChildren[$productId] = $newProductData;
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

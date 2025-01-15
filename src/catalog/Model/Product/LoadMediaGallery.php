<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Gallery as Resource;

class LoadMediaGallery
{
    private Resource $resourceModel;
    private ProductMetaData $productMetaData;
    private array $rowIdToEntityId = [];

    public function __construct(
        Resource $resource,
        ProductMetaData $productMetaData
    ) {
        $this->resourceModel = $resource;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $indexData, int $storeId): array
    {
        $this->mapRowIdToEntityId($indexData);
        $linkField = $this->productMetaData->get()->getLinkField();
        $linkFieldIds = array_column($indexData, $linkField);

        $gallerySet = $this->resourceModel->loadGallerySet($linkFieldIds, $storeId);

        foreach ($indexData as &$productData) {
            $productData['gallery'] = [];
        }

        foreach ($gallerySet as $mediaImage) {
            $linkFieldId  = $mediaImage['row_id'];
            $entityId = $this->rowIdToEntityId[$linkFieldId] ?? $linkFieldId;

            $image = [
                'url' => $mediaImage['file'], // TODO full url?
                'alt' => $this->getValue('label', $mediaImage)
            ];

            $indexData[$entityId]['gallery'][] = $image;
        }

        $this->rowIdToEntityId = [];

        return $indexData;
    }

    private function mapRowIdToEntityId(array $products): void
    {
        $linkField = $this->productMetaData->get()->getLinkField();
        $identifierField = $this->productMetaData->get()->getIdentifierField();

        if ($identifierField !== $linkField) {
            foreach ($products as $entityId => $product) {
                $this->rowIdToEntityId[$product[$linkField]] = $entityId;
            }
        }
    }

    private function getValue(string $fieldKey, array $image): string
    {
        if (isset($image[$fieldKey]) && (null !== $image[$fieldKey])) {
            return $image[$fieldKey];
        }

        if (isset($image[$fieldKey . '_default'])) {
            return $image[$fieldKey . '_default'];
        }

        return '';
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

use StreamX\ConnectorCatalog\Api\LoadMediaGalleryInterface;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Gallery as Resource;

class LoadMediaGallery implements LoadMediaGalleryInterface
{
    const VIDEO_TYPE = 'external-video';

    private string $youtubeRegex =
        '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

    private array $vimeoRegex = [
        '%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)',
        "?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im",
    ];

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
        $valueIds = $this->getValueIds($gallerySet);
        $videoSet = $this->resourceModel->loadVideos($valueIds, $storeId);

        foreach ($indexData as &$productData) {
            $productData['media_gallery'] = [];
        }

        foreach ($gallerySet as $mediaImage) {
            $linkFieldId  = $mediaImage['row_id'];
            $entityId = $this->rowIdToEntityId[$linkFieldId] ?? $linkFieldId;

            $image['typ'] = 'image';
            $image        = [
                'typ' => 'image',
                'image' => $mediaImage['file'],
                'lab' => $this->getValue('label', $mediaImage),
                'pos' => (int)($this->getValue('position', $mediaImage)),
            ];

            $valueId = $mediaImage['value_id'];

            if (isset($videoSet[$valueId])) {
                $image['vid'] = $this->prepareVideoData($videoSet[$valueId]);
            }

            $indexData[$entityId]['media_gallery'][] = $image;
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

    private function getValueIds(array $mediaGallery): array
    {
        $valueIds = [];

        foreach ($mediaGallery as $mediaItem) {
            if (self::VIDEO_TYPE === $mediaItem['media_type']) {
                $valueIds[] = $mediaItem['value_id'];
            }
        }

        return $valueIds;
    }

    private function prepareVideoData(array $video): array
    {
        $vimeoRegex = implode('', $this->vimeoRegex);
        $id = null;
        $type = null;
        $reg = [];
        $url = $video['url'];

        if (preg_match($this->youtubeRegex, $url, $reg)) {
            $id = $reg[1];
            $type = 'youtube';
        } elseif (preg_match($vimeoRegex, $video['url'], $reg)) {
            $id = $reg[3];
            $type = 'vimeo';
        }

        $video['video_id'] = $id;
        $video['type'] = $type;

        return $video;
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

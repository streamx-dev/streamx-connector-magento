<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerCatalog\Model\ProductMetaData;
use Magento\Catalog\Model\ResourceModel\Product\Gallery as GalleryResource;
use Magento\Eav\Model\Entity\Attribute as EntityAttribute;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;

class Gallery
{
    /**
     * @var array
     */
    private $videoProperties = [
        'url' => 'url',
        'title' => 'title',
        'desc' => 'description',
        'meta' => 'metadata',
    ];

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var EntityAttribute
     */
    private $entityAttribute;

    /**
     * @var ProductMetaData
     */
    private $metadataPool;

    public function __construct(
        ProductMetaData $metadataPool,
        ResourceConnection $resourceModel,
        EntityAttribute $attribute
    ) {
        $this->metadataPool = $metadataPool;
        $this->entityAttribute = $attribute;
        $this->resource = $resourceModel;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function loadGallerySet(array $linkFieldIds, int $storeId)
    {
        $select = $this->getLoadGallerySelect($linkFieldIds, $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getMediaGalleryAttributeId()
    {
        $attribute = $this->entityAttribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, 'media_gallery');

        return $attribute->getId();
    }

    /**
     * @return array
     */
    public function loadVideos(array $valueIds, int $storeId)
    {
        if (empty($valueIds)) {
            return [];
        }

        $result = $this->getVideoRawData($valueIds, $storeId);
        $groupByValueId = [];

        foreach ($result as $item) {
            $valueId = $item['value_id'];
            $item = $this->substituteNullsWithDefaultValues($item);
            unset($item['value_id']);
            $groupByValueId[$valueId] = $item;
        }

        return $groupByValueId;
    }

    /**
     * @return array
     */
    private function getVideoRawData(array $valueIds, int $storeId)
    {
        $connection = $this->getConnection();
        $mainTableAlias = 'main';
        $videoTable = $this->resource->getTableName('catalog_product_entity_media_gallery_value_video');

        // Select gallery images for product
        $select = $connection->select()
            ->from(
                [$mainTableAlias => $videoTable],
                [
                    'value_id' => 'value_id',
                    'url_default' => 'url',
                    'title_default' => 'title',
                    'desc_default' => 'description',
                    'meta_default' => 'metadata'
                ]
            );

        $select->where($mainTableAlias . '.store_id = ?', Store::DEFAULT_STORE_ID);
        $select->where($mainTableAlias . '.value_id IN (?)', $valueIds);

        $select->joinLeft(
            ['value' => $videoTable],
            implode(
                ' AND ',
                [
                    $mainTableAlias . '.value_id = value.value_id',
                    $this->getConnection()->quoteInto('value.store_id = ?', (int)$storeId),
                ]
            ),
            $this->videoProperties
        );

        return $connection->fetchAll($select);
    }

    /**
     * @return array
     */
    private function substituteNullsWithDefaultValues(array $rowData)
    {
        $columns = array_keys($this->videoProperties);

        foreach ($columns as $key) {
            if (empty($rowData[$key]) && !empty($rowData[$key . '_default'])) {
                $rowData[$key] = $rowData[$key . '_default'];
            }

            unset($rowData[$key . '_default']);
        }

        return $rowData;
    }

    /**
     * @return Select
     * @throws \Exception
     */
    private function getLoadGallerySelect(array $linkFieldIds, int $storeId)
    {
        $linkField = $this->metadataPool->get()->getLinkField();
        $attributeId = $this->getMediaGalleryAttributeId();
        $connection = $this->getConnection();

        $mainTableAlias = 'main';
        $positionCheckSql = $this->getConnection()->getCheckSql(
            'value.position IS NULL',
            'default_value.position',
            'value.position'
        );

        // Select gallery images for product
        $select = $connection->select()
            ->from(
                [$mainTableAlias => $this->resource->getTableName(GalleryResource::GALLERY_TABLE)],
                [
                    'value_id',
                    'media_type',
                    'file' => 'value'
                ]
            )->joinInner(
                ['entity' => $this->resource->getTableName(GalleryResource::GALLERY_VALUE_TO_ENTITY_TABLE)],
                $mainTableAlias . '.value_id = entity.value_id',
                []
            )
            ->joinLeft(
                ['value' => $this->resource->getTableName(GalleryResource::GALLERY_VALUE_TABLE)],
                implode(
                    ' AND ',
                    [
                        $mainTableAlias . '.value_id = value.value_id',
                        $this->getConnection()->quoteInto('value.store_id = ?', (int)$storeId),
                        'value.' . $linkField . ' = entity.' . $linkField,
                    ]
                ),
                []
            )
            ->joinLeft( // Joining default values
                ['default_value' => $this->resource->getTableName(GalleryResource::GALLERY_VALUE_TABLE)],
                implode(
                    ' AND ',
                    [
                        $mainTableAlias . '.value_id = default_value.value_id',
                        $this->getConnection()->quoteInto('default_value.store_id = ?', Store::DEFAULT_STORE_ID),
                        'default_value.' . $linkField . ' = entity.' . $linkField,
                    ]
                ),
                []
            )
            ->columns([
                'row_id' => 'entity.'.$linkField,
                'label' => $this->getConnection()->getIfNullSql('`value`.`label`', '`default_value`.`label`'),
                'position' => $this->getConnection()->getIfNullSql('`value`.`position`', '`default_value`.`position`'),
                'label_default' => 'default_value.label',
                'position_default' => 'default_value.position',
            ])
            ->where('main.attribute_id = ?', $attributeId)
            ->where('entity.' . $linkField . ' IN (?)', $linkFieldIds)
            ->where('default_value.disabled is NULL or default_value.disabled != 1')
            ->where('value.disabled is NULL or value.disabled != 1')
            ->order($positionCheckSql . ' ' . Select::SQL_ASC);

        return $select;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }
}

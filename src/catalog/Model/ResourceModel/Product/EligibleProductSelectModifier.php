<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use DomainException;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class EligibleProductSelectModifier implements SelectModifierInterface
{
    private ProductMetaData $productMetaData;
    private ResourceConnection $resourceConnection;
    private StoreManagerInterface $storeManager;
    private CatalogConfig $catalogConfig;
    private int $statusAttributeId;
    private string $statusAttributeBackendTable;
    private int $visibilityAttributeId;
    private string $visibilityAttributeBackendTable;

    public function __construct(
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        CatalogConfig $catalogConfig,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->productMetaData = $productMetaData;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
        $this->catalogConfig = $catalogConfig;
        $this->loadAttributes($attributeCollectionFactory);
    }

    public function modify(Select $select, int $storeId): void
    {
        $this->addStatusCondition($select, $storeId);
        if (!$this->catalogConfig->shouldExportProductsNotVisibleIndividually()) {
            $this->addVisibilityCondition($select, $storeId);
        }
        $this->addWebsiteCondition($select, $storeId);
    }

    public function modifyWithIrrelevantVisibility(Select $select, int $storeId): void
    {
        $this->addStatusCondition($select, $storeId);
        $this->addWebsiteCondition($select, $storeId);
    }

    private function addStatusCondition(Select $select, int $storeId): void
    {
        $linkField = $this->productMetaData->getLinkField();
        $backendTable = $this->resourceConnection->getTableName($this->statusAttributeBackendTable);

        $select->joinLeft(
            ['s' => $backendTable], // default status
            "s.attribute_id = $this->statusAttributeId AND s.store_id = 0 AND s.$linkField = entity.$linkField",
            []
        )->joinLeft(
            ['ss' => $backendTable], // store level status
            "ss.attribute_id = $this->statusAttributeId AND ss.store_id = $storeId AND ss.$linkField = entity.$linkField",
            []
        )->where('CASE WHEN ss.value_id > 0 THEN ss.value = ? ELSE s.value = ? END', Status::STATUS_ENABLED);
    }

    private function addVisibilityCondition(Select $select, int $storeId): void
    {
        $linkField = $this->productMetaData->getLinkField();
        $backendTable = $this->resourceConnection->getTableName($this->visibilityAttributeBackendTable);

        $select->joinLeft(
            ['v' => $backendTable], // default visibility
            "v.attribute_id = $this->visibilityAttributeId AND v.store_id = 0 AND v.$linkField = entity.$linkField",
            []
        )->joinLeft(
            ['sv' => $backendTable], // store level visibility
            "sv.attribute_id = $this->visibilityAttributeId AND sv.store_id = $storeId AND sv.$linkField = entity.$linkField",
            []
        )->where('CASE WHEN sv.value_id > 0 THEN sv.value <> ? ELSE v.value <> ? END', Visibility::VISIBILITY_NOT_VISIBLE);
    }

    private function addWebsiteCondition(Select $select, int $storeId): void
    {
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $tableName = $this->resourceConnection->getTableName('catalog_product_website');

        $select->join(
            $tableName,
            "$tableName.product_id = entity.entity_id AND $tableName.website_id = $websiteId",
            []
        );
    }

    private function loadAttributes(CollectionFactory $attributeCollectionFactory): void
    {
        $statusAttribute = $this->loadAttribute($attributeCollectionFactory, 'status');
        $this->statusAttributeId = (int) $statusAttribute->getId();
        $this->statusAttributeBackendTable = $statusAttribute->getBackendTable();

        $visibilityAttribute = $this->loadAttribute($attributeCollectionFactory, 'visibility');
        $this->visibilityAttributeId = (int) $visibilityAttribute->getId();
        $this->visibilityAttributeBackendTable = $visibilityAttribute->getBackendTable();
    }

    private function loadAttribute(CollectionFactory $attributeCollectionFactory, string $attributeCode): AbstractModel
    {
        $attributeCollection = $attributeCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_code', $attributeCode)
            ->setPageSize(1);

        foreach ($attributeCollection as $attribute) {
            return $attribute;
        }

        throw new DomainException("Cannot load $attributeCode attribute");
    }
}

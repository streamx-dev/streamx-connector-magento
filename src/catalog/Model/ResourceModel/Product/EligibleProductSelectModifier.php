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
        $this->modifyByStatus($select, $storeId);
        $this->modifyByVisibility($select, $storeId);
        $this->modifyByWebsite($select, $storeId);
    }

    public function modifyWithIrrelevantVisibility(Select $select, int $storeId): void
    {
        $this->modifyByStatus($select, $storeId);
        $this->modifyByWebsite($select, $storeId);
    }

    public function modifyNegate(Select $select, int $storeId): void
    {
        // TODO: Implement modifyNegate() method.
    }

    private function modifyByStatus(Select $select, int $storeId): void
    {
        $linkField = $this->productMetaData->getLinkField();
        $backendTable = $this->resourceConnection->getTableName($this->statusAttributeBackendTable);

        $select->joinLeft(
            ['d' => $backendTable],
            "d.attribute_id = $this->statusAttributeId AND d.store_id = 0 AND d.$linkField = entity.$linkField",
            []
        )->joinLeft(
            ['c' => $backendTable],
            "c.attribute_id = $this->statusAttributeId AND c.store_id = $storeId AND c.$linkField = entity.$linkField",
            []
        )->where("CASE WHEN c.value_id > 0 THEN c.value = ? ELSE d.value = ? END", Status::STATUS_ENABLED);
    }

    private function modifyByVisibility(Select $select, int $storeId): void
    {
        if ($this->catalogConfig->shouldExportProductsNotVisibleIndividually()) {
            return;
        }

        $linkField = $this->productMetaData->getLinkField();
        $backendTable = $this->resourceConnection->getTableName($this->visibilityAttributeBackendTable);

        $select->joinLeft(
            ['d2' => $backendTable],
            "d2.attribute_id = $this->visibilityAttributeId AND d2.store_id = 0 AND d2.$linkField = entity.$linkField",
            []
        )->joinLeft(
            ['c2' => $backendTable],
            "c2.attribute_id = $this->visibilityAttributeId AND c2.store_id = $storeId AND c2.$linkField = entity.$linkField",
            []
        )->where("CASE WHEN c2.value_id > 0 THEN c2.value <> ? ELSE d2.value <> ? END", Visibility::VISIBILITY_NOT_VISIBLE);
    }

    private function modifyByWebsite(Select $select, int $storeId): void
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

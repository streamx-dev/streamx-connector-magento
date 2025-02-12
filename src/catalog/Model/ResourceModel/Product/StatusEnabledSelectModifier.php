<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use DomainException;
use Exception;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class StatusEnabledSelectModifier implements SelectModifierInterface
{
    private ProductMetaData $productMetaData;
    private ResourceConnection $resourceConnection;
    private Attribute $statusAttribute;

    public function __construct(
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->productMetaData = $productMetaData;
        $this->resourceConnection = $resourceConnection;
        $this->statusAttribute = $this->loadStatusAttribute($attributeCollectionFactory);
    }

    /**
     * Process the select statement - filter products to select only enabled products
     * @throws Exception
     */
    public function modify(Select $select, int $storeId): void
    {
        $linkField = $this->productMetaData->get()->getLinkField();
        $connection = $this->resourceConnection->getConnection();
        $backendTable = $this->resourceConnection->getTableName($this->statusAttribute->getBackendTable());

        $select->joinLeft(
            ['d' => $backendTable],
            $connection->quoteInto(
                "d.attribute_id = ? AND d.store_id = 0 AND d.$linkField = entity.$linkField",
                $this->statusAttribute->getAttributeId()
            ),
            []
        )->joinLeft(
            ['c' => $backendTable],
            $connection->quoteInto(
                "c.attribute_id = ? AND c.store_id = ? AND c.$linkField = entity.$linkField",
                $this->statusAttribute->getAttributeId(), $storeId
            ),
            []
        )->where("CASE WHEN c.value_id > 0 THEN c.value = ? ELSE d.value = ? END", Status::STATUS_ENABLED);
    }

    private function loadStatusAttribute(CollectionFactory $attributeCollectionFactory): Attribute
    {
        $attributeCollection = $attributeCollectionFactory
            ->create()
            ->addFieldToFilter('attribute_code', 'status')
            ->setPageSize(1);

        foreach ($attributeCollection as $attribute) {
            return $attribute;
        }

        throw new DomainException("Cannot load status attribute");
    }
}

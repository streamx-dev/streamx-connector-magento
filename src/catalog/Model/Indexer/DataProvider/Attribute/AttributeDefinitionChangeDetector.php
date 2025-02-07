<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributeDefinitions;
use StreamX\ConnectorCore\Indexer\StoreManager;

class AttributeDefinitionChangeDetector
{
    private const STREAMX_PRODUCT_ATTRIBUTE_STATE_TABLE = 'streamx_product_attribute_state';

    private const COLUMN_ID = 'id';
    private const COLUMN_STORE_ID = 'store_id';
    private const COLUMN_ATTRIBUTE_ID = 'attribute_id';
    private const COLUMN_SERIALIZED_STATE = 'serialized_state';

    private LoadAttributeDefinitions $loadAttributeDefinitions;
    private StoreManager $storeManager;
    private ResourceConnection $resourceConnection;
    private bool $enabled;

    public function __construct(
        LoadAttributeDefinitions $loadAttributeDefinitions,
        StoreManager $storeManager,
        ResourceConnection $resourceConnection,
        bool $enabled
    )
    {
        $this->loadAttributeDefinitions = $loadAttributeDefinitions;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->enabled = $enabled;

        // TODO: now this code is called after first attribute change has happened, because this class is instantiated to handle such changes.
        //  Invent way / place to call the code before any attr change:
        $this->initStreamxProductAttributeStateTable();
    }

    private function initStreamxProductAttributeStateTable(): void
    {
        if (!$this->enabled) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        if (!$connection->isTableExists(self::STREAMX_PRODUCT_ATTRIBUTE_STATE_TABLE)) {
            $table = $connection
                ->newTable(self::STREAMX_PRODUCT_ATTRIBUTE_STATE_TABLE)
                ->addColumn(self::COLUMN_ID, Table::TYPE_INTEGER, null, ['primary' => true, 'identity' => true])
                ->addColumn(self::COLUMN_STORE_ID, Table::TYPE_INTEGER)
                ->addColumn(self::COLUMN_ATTRIBUTE_ID, Table::TYPE_INTEGER)
                ->addColumn(self::COLUMN_SERIALIZED_STATE, Table::TYPE_TEXT);
            $connection->createTable($table);

            $rowsToInsert = [];
            foreach ($this->storeManager->getStores() as $store) {
                $storeId = $store->getId();
                $attributesForStore = $this->loadAttributeDefinitions->loadAttributeDefinitionsByIds([], $storeId);
                foreach ($attributesForStore as $attribute) {
                    $rowsToInsert[] = [
                        self::COLUMN_STORE_ID => $storeId,
                        self::COLUMN_ATTRIBUTE_ID => $attribute->getId(),
                        self::COLUMN_SERIALIZED_STATE => json_encode($attribute)
                    ];
                }
            }
            $connection->insertMultiple(self::STREAMX_PRODUCT_ATTRIBUTE_STATE_TABLE, $rowsToInsert);
            $connection->commit();
        }
    }

    public function hasAttributeDefinitionChanged(?AttributeDefinition $attributeDefinition, int $storeId): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(self::STREAMX_PRODUCT_ATTRIBUTE_STATE_TABLE, [self::COLUMN_SERIALIZED_STATE])
            ->where(self::COLUMN_ATTRIBUTE_ID . ' = ?', $attributeDefinition->getId())
            ->where(self::COLUMN_STORE_ID . ' = ?', $storeId);

        $oldAttributeDefinitionRow = $connection->fetchRow($select);

        if (!$oldAttributeDefinitionRow) {
            // we didn't have this attribute definition loaded to the table yet, so treat is as a probably newly added attribute
            return true;
        }

        $oldAttributeDefinition = AttributeDefinition::fromJson($oldAttributeDefinitionRow[self::COLUMN_SERIALIZED_STATE]);

        return !$oldAttributeDefinition->isSameAs($attributeDefinition);
    }

    public function updateAttributeDefinitionInTable(AttributeDefinition $attributeDefinition, int $storeId): void
    {
        if (!$this->enabled) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $rowToUpsert = [
            self::COLUMN_STORE_ID => $storeId,
            self::COLUMN_ATTRIBUTE_ID => $attributeDefinition->getId(),
            self::COLUMN_SERIALIZED_STATE => json_encode($attributeDefinition)
        ];
        $connection->insertOnDuplicate(self::STREAMX_PRODUCT_ATTRIBUTE_STATE_TABLE, [$rowToUpsert], [self::COLUMN_ID]);
        $connection->commit();
    }
}

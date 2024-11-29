<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel;

use Divante\VsbridgeIndexerCore\Api\ConvertValueInterface;
use Divante\VsbridgeIndexerCore\Api\MappingInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\EntityMetadataInterface;

abstract class AbstractEavAttributes implements EavAttributesInterface
{
    /**
     * @var array
     */
    private $restrictedAttribute = [
        'quantity_and_stock_status',
        'options_container',
    ];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $attributesById;

    /**
     * @var string
     */
    private $entityType;

    /**
     * @var array
     */
    private $valuesByEntityId;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var MappingInterface
     */
    private $mapping;

    /**
     * @var ConvertValueInterface
     */
    private $convertValue;

    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        ConvertValueInterface $convertValue,
        MappingInterface $mapping,
        $entityType
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->entityType = $entityType;
        $this->convertValue = $convertValue;
        $this->mapping = $mapping;
    }

    /**
     * Load attributes
     * @return mixed
     */
    abstract public function initAttributes();

    /**
     * @throws \Exception
     */
    public function loadAttributesData(int $storeId, array $entityIds, array $requiredAttributes = null): array
    {
        $this->attributesById = $this->initAttributes();
        $tableAttributes = [];
        $attributeTypes = [];
        $selects = [];

        foreach ($this->attributesById as $attributeId => $attribute) {
            if ($this->canIndexAttribute($attribute, $requiredAttributes)) {
                $tableAttributes[$attribute->getBackendTable()][] = $attributeId;

                if (!isset($attributeTypes[$attribute->getBackendTable()])) {
                    $attributeTypes[$attribute->getBackendTable()] = $attribute->getBackendType();
                }
            }
        }

        foreach ($tableAttributes as $table => $attributeIds) {
            $select = $this->getLoadAttributesSelect($storeId, $table, $attributeIds, $entityIds);
            $selects[$table] = $select;
        }

        $this->valuesByEntityId = [];

        if (!empty($selects)) {
            foreach ($selects as $select) {
                $values = $this->getConnection()->fetchAll($select);
                $this->processValues($values);
            }
        }

        return $this->valuesByEntityId;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function canIndexAttribute(\Magento\Eav\Model\Entity\Attribute $attribute, array $allowedAttributes = null)
    {
        if ($attribute->isStatic()) {
            return false;
        }

        if (in_array($attribute->getAttributeCode(), $this->restrictedAttribute)) {
            return false;
        }

        if (null === $allowedAttributes || empty($allowedAttributes)) {
            return true;
        }

        return in_array($attribute->getAttributeCode(), $allowedAttributes);
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function processValues(array $values)
    {
        foreach ($values as $value) {
            $entityIdField = $this->getEntityMetaData()->getIdentifierField();
            $entityId = $value[$entityIdField];
            $attribute = $this->attributesById[$value['attribute_id']];
            $attributeCode = $attribute->getAttributeCode();

            if ($attribute->getFrontendInput() === 'multiselect') {
                $options = explode(',', $value['value']);

                if (!empty($options)) {
                    $options = array_map([$this, 'parseValue'], $options);
                }

                $value['value'] = $options;
            } else {
                $value['value'] = $this->prepareValue(
                    $attributeCode,
                    $value['value']
                );
            }

            $this->valuesByEntityId[$entityId][$attributeCode] = $value['value'];
        }

        return $this->valuesByEntityId;
    }

    /**
     * @param array|string $value
     *
     * @return array|string|int|float
     * @throws \Exception
     */
    private function prepareValue(string $attributeCode, $value)
    {
        return $this->convertValue->execute($this->mapping, $attributeCode, $value);
    }

    /**
     * Parse the option value - Cast to int if it's numeric
     * otherwise leave it as-is
     *
     * @param mixed $value
     *
     * @return mixed
     * @SuppressWarnings("unused")
     */
    private function parseValue($value)
    {
        return is_numeric($value) ? intval($value) : $value;
    }

    /**
     * Retrieve attributes load select
     *
     * @return \Magento\Framework\DB\Select
     * @throws \Exception
     */
    private function getLoadAttributesSelect(int $storeId, string $table, array $attributeIds, array $entityIds)
    {
        //  Either row_id (enterpise/commerce version) or entity_id.
        $linkField = $this->getEntityMetaData()->getLinkField();
        $entityIdField = $this->getEntityMetaData()->getIdentifierField();

        $joinStoreCondition = [
            "t_default.$linkField=t_store.$linkField",
            't_default.attribute_id=t_store.attribute_id',
            't_store.store_id=?',
        ];

        $joinCondition = $this->getConnection()->quoteInto(
            implode(' AND ', $joinStoreCondition),
            $storeId
        );

        $valueExpr = $this->getConnection()->getCheckSql(
            't_store.value_id IS NULL',
            't_default.value',
            't_store.value'
        );

        return $this->getConnection()->select()
            ->from(['entity' => $this->getEntityMetaData()->getEntityTable()], [$entityIdField])
            ->joinInner(
                ['t_default' => $table],
                new \Zend_Db_Expr("entity.{$linkField} = t_default.{$linkField}"),
                ['attribute_id']
            )
            ->joinLeft(
                ['t_store' => $table],
                $joinCondition,
                ['value' => $valueExpr]
            )
            ->where("entity.$entityIdField IN (?)", $entityIds)
            ->where('t_default.attribute_id IN (?)', $attributeIds)
            ->where(
                't_default.store_id = ?',
                $this->getConnection()->getIfNullSql('t_store.store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            );
    }

    /**
     * Retrieve Metadata for an entity (product or category)
     *
     * @return EntityMetadataInterface
     * @throws \Exception
     */
    private function getEntityMetaData()
    {
        return $this->metadataPool->getMetadata($this->entityType);
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resourceConnection->getConnection();
    }
}

<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Attribute\Save;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class UpdateAttributeDataPlugin
{
    /**
     * @var AttributeProcessor
     */
    private $attributeProcessor;

    /**
     * @var ProductProcessor
     */
    private $productProcessor;

    public function __construct(
        ProductProcessor $processor,
        AttributeProcessor $attributeProcessor
    ) {
        $this->productProcessor = $processor;
        $this->attributeProcessor = $attributeProcessor;
    }

    /**
     * TODO check if we add new attribute, after adding new attribute send request to elastic to add new mapping
     * for field.
     *
     * @return Attribute
     */
    public function afterAfterSave(Attribute $attribute)
    {
        $this->attributeProcessor->reindexRow($attribute->getId());

        return $attribute;
    }

    /**
     * After deleting attribute we should update all products
     *
     * @return Attribute
     */
    public function afterAfterDeleteCommit(Attribute $attribute, Attribute $result)
    {
        $this->attributeProcessor->reindexRow($attribute->getId());
        $this->productProcessor->markIndexerAsInvalid();

        return $result;
    }
}

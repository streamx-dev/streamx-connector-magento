<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Attribute\Save;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class UpdateAttributeDataPlugin
{
    private AttributeProcessor $attributeProcessor;
    private ProductProcessor $productProcessor;

    public function __construct(
        ProductProcessor $processor,
        AttributeProcessor $attributeProcessor
    ) {
        $this->productProcessor = $processor;
        $this->attributeProcessor = $attributeProcessor;
    }

    public function afterAfterSave(Attribute $attribute): Attribute
    {
        $this->attributeProcessor->reindexRow($attribute->getId());

        return $attribute;
    }

    /**
     * After deleting attribute we should update all products
     */
    public function afterAfterDeleteCommit(Attribute $attribute, Attribute $result): Attribute
    {
        $this->attributeProcessor->reindexRow($attribute->getId());
        $this->productProcessor->markIndexerAsInvalid();

        return $result;
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Plugin\Indexer\Attribute\Save;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\AttributeProcessor;
use Divante\VsbridgeIndexerCatalog\Model\Indexer\ProductProcessor;
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
     * @param Attribute $attribute
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
     * @param Attribute $attribute
     * @param Attribute $result
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

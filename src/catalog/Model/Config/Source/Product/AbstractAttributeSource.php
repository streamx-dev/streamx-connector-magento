<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

abstract class AbstractAttributeSource implements OptionSourceInterface
{
    private ?array $options = null;
    private CollectionFactory $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (null === $this->options) {
            $this->options = [];
            $this->options[] = [
                'value' => '',
                'label' => __('-- All Attributes --'),
            ];
            /** @var Collection $collection */
            $collection = $this->collectionFactory->create();
            $collection->addVisibleFilter();
            $attributes = $collection->getItems();

            /** @var ProductAttributeInterface $attribute */
            foreach ($attributes as $attribute) {
                if ($this->canAddAttribute($attribute)) {
                    $label = sprintf(
                        '%s (%s)',
                        $attribute->getDefaultFrontendLabel(),
                        $attribute->getAttributeCode()
                    );

                    $this->options[] = [
                        'label' => $label,
                        'value' => $attribute->getAttributeCode(),
                    ];
                }
            }
        }

        return $this->options;
    }

    /**
     * Validate if attribute can be shown
     */
    abstract public function canAddAttribute(ProductAttributeInterface $attribute): bool;
}

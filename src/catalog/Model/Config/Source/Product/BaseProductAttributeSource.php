<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;

abstract class BaseProductAttributeSource implements OptionSourceInterface
{
    // always loaded product attributes - don't allow the user to select them or not
    public const ALWAYS_LOADED_ATTRIBUTES = [
        'name',
        'image',
        'description',
        'price',
        'url_key',
        'media_gallery'
    ];

    private const ADDITIONAL_ATTRIBUTES_NOT_ALLOWED_IN_SELECT_LIST = [
        'gallery',
        'category_ids',
        'swatch_image',
        'quantity_and_stock_status',
        'options_container',
    ];

    private ?array $options = null;
    private CollectionFactory $collectionFactory;
    private array $attributesNotAllowedInSelectList;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
        $this->attributesNotAllowedInSelectList = array_merge(
            self::ALWAYS_LOADED_ATTRIBUTES,
            self::ADDITIONAL_ATTRIBUTES_NOT_ALLOWED_IN_SELECT_LIST
        );
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        if (null === $this->options) {
            $this->options = [];
            $this->options[] = [
                'value' => '',
                'label' => __('-- All Attributes --'),
            ];

            $collection = $this->collectionFactory
                ->create()
                ->addVisibleFilter()
                ->addOrder('frontend_label', Collection::SORT_ORDER_ASC);
            $attributes = $collection->getItems();

            /** @var ProductAttributeInterface $attribute */
            foreach ($attributes as $attribute) {
                if ($this->isAllowedInSelectList($attribute)) {
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
    public function isAllowedInSelectList(ProductAttributeInterface $attribute): bool
    {
        return !in_array($attribute->getAttributeCode(), $this->attributesNotAllowedInSelectList);
    }
}

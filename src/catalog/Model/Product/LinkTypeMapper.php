<?php

namespace StreamX\ConnectorCatalog\Model\Product;

use Magento\Catalog\Model\Product\Link as ProductLink;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link as GroupedLink;

class LinkTypeMapper
{

    /**
     * Product link type mapping, used for references and validation
     *
     * @var array
     */
    private $linkTypesMap = [
        ProductLink::LINK_TYPE_RELATED => 'related',
        ProductLink::LINK_TYPE_UPSELL => 'upsell',
        ProductLink::LINK_TYPE_CROSSSELL => 'crosssell',
        GroupedLink::LINK_TYPE_GROUPED => 'associated',
    ];

    public function map(int $typeId): ?string
    {
        $linksTypesMap = $this->getTypesMap();

        if (isset($linksTypesMap[$typeId])) {
            return $linksTypesMap[$typeId];
        }

        return null;
    }

    public function getTypesMap(): array
    {
        return $this->linkTypesMap;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Plugin\Mview;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use Magento\Framework\Mview\ViewInterface;

class ViewPlugin
{
    private CatalogConfigurationInterface $catalogSettings;

    public function __construct(CatalogConfigurationInterface $catalogSettings)
    {
        $this->catalogSettings = $catalogSettings;
    }

    public function afterGetSubscriptions(ViewInterface $subject, array $result): array
    {
        if ($this->catalogSettings->useCatalogRules() && $this->isStreamxProductIndexer($subject)) {
            $result['catalogrule_product_price'] = [
                'name' => 'catalogrule_product_price',
                'column' => 'product_id',
                'subscription_model' => null,
            ];
        }

        return $result;
    }

    private function isStreamxProductIndexer(ViewInterface $subject): bool
    {
        return ProductProcessor::INDEXER_ID === $subject->getId();
    }
}

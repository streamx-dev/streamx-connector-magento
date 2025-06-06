<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Mview;

use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use Magento\Framework\Mview\ViewInterface;

class ViewPlugin
{
    private CatalogConfig $catalogSettings;

    public function __construct(CatalogConfig $catalogSettings)
    {
        $this->catalogSettings = $catalogSettings;
    }

    public function afterGetSubscriptions(ViewInterface $subject, array $result): array
    {
        // if useCatalogPriceRules is true -> add catalogrule_product_price table to tables that streamx_product_indexer subscribes on in mView mode
        if ($this->catalogSettings->useCatalogPriceRules() && $this->isStreamxProductIndexer($subject)) {
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
        return ProductIndexer::INDEXER_ID === $subject->getId();
    }
}

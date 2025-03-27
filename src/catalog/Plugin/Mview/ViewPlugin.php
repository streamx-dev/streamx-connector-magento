<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Mview;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use Magento\Framework\Mview\ViewInterface;

class ViewPlugin
{
    private CatalogConfig $catalogSettings;

    public function __construct(CatalogConfig $catalogSettings)
    {
        $this->catalogSettings = $catalogSettings;
    }

    // TODO review is this needed
    public function afterGetSubscriptions(ViewInterface $subject, array $result): array
    {
        // TODO: originally, useCatalogPriceRules could be set to true also when usePricesIndex was false.
        //  Now, useCatalogPriceRules can be set to true only if also usePricesIndex is true, otherwise it can be only false.
        //  Analyse if it is OK
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
        return ProductProcessor::INDEXER_ID === $subject->getId();
    }
}

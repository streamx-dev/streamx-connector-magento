<?php

declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Magento\Framework\Locale\Resolver;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class LangData implements DataProviderInterface
{
    private Resolver $localeResolver;

    public function __construct(Resolver $localeResolver) {
        $this->localeResolver = $localeResolver;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $currentLanguage = $this->getCurrentLanguage();

        foreach ($indexData as &$product) {
            $product['lang'] = $currentLanguage;
        }

        return $indexData;
    }

    private function getCurrentLanguage(): string
    {
        $currentLocaleCode = $this->localeResolver->getLocale();
        return str_contains($currentLocaleCode, '_')
            ? strstr($currentLocaleCode, '_', true)
            : $currentLocaleCode;
    }
}

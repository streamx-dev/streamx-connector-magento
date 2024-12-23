<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

use Magento\Framework\Exception\InputException;

class ParentResolver
{
    /**
     * @var GetParentsByChildIdInterface[]
     */
    private array $parentProviders = [];

    private array $parentSkus = [];

    /**
     * @throws InputException
     */
    public function __construct(array $handlers)
    {
        foreach ($handlers as $handler) {
            if (!$handler instanceof GetParentsByChildIdInterface) {
                throw new InputException(
                    __(
                        'Parent handler %1 doesn\'t implement GetParentsByChildIdInterface',
                        get_class($handler)
                    )
                );
            }
        }

        $this->parentProviders = $handlers;
    }

    public function load(array $childIds): void
    {
        $this->parentSkus = [];

        foreach ($this->parentProviders as $type => $provider) {
            $this->parentSkus[$type] = $provider->execute($childIds);
        }
    }

    public function resolveParentSku(int $childId): array
    {
        $fullSkuList = [];

        foreach ($this->parentProviders as $type => $provider) {
            $sku = $this->parentSkus[$type][$childId] ?? [];
            $fullSkuList = array_merge($sku, $fullSkuList);
        }

        return $fullSkuList;
    }
}

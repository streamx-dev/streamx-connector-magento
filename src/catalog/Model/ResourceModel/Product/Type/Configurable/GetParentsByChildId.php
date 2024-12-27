<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product\Type\Configurable;

use StreamX\ConnectorCatalog\Model\Product\GetParentsByChildIdInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Attribute\OptionProvider;
use Magento\Framework\App\ResourceConnection;

class GetParentsByChildId implements GetParentsByChildIdInterface
{
    private ResourceConnection $resourceConnection;
    private OptionProvider $optionProvider;

    public function __construct(
        OptionProvider $optionProvider,
        ResourceConnection $resourceConnection
    ) {
        $this->optionProvider = $optionProvider;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $childId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $parentSku = [];
        $select = $connection->select()
            ->from(['l' => 'catalog_product_super_link'], ['l.product_id'])
            ->join(
                ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'e.' . $this->optionProvider->getProductEntityLinkField() . ' = l.parent_id',
                ['e.sku']
            )->where('l.product_id IN(?)', $childId);

        foreach ($connection->fetchAll($select) as $row) {
            $parentSku[$row['product_id']] = $parentSku[$row['product_id']] ?? [];
            $parentSku[$row['product_id']][] = $row['sku'];
        }

        return $parentSku;
    }
}

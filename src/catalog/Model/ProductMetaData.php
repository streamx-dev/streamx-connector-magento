<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Framework\EntityManager\MetadataPool;

class ProductMetaData {

    private string $entityTable;
    private string $identifierField;
    private string $linkField;
    private int $entityTypeId;

    public function __construct(MetadataPool $metadataPool, Config $eavConfig) {
        $productMetaData = $metadataPool->getMetadata(ProductInterface::class);
        $this->entityTable = $productMetaData->getEntityTable();
        $this->identifierField = $productMetaData->getIdentifierField();
        $this->linkField = $productMetaData->getLinkField();
        $this->entityTypeId = (int) $eavConfig->getEntityType(Product::ENTITY)->getId();
    }

    public function getEntityTable(): string {
        return $this->entityTable;
    }

    public function getIdentifierField(): string {
        return $this->identifierField;
    }

    public function getLinkField(): string {
        return $this->linkField;
    }

    public function getEntityTypeId(): int {
        return $this->entityTypeId;
    }
}

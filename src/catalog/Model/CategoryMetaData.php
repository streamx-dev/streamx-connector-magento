<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config;
use Magento\Framework\EntityManager\MetadataPool;

class CategoryMetaData {

    private string $entityTable;
    private string $entityIdField;
    private string $linkField;
    private int $entityTypeId;

    public function __construct(MetadataPool $metadataPool, Config $eavConfig) {
        $categoryMetaData = $metadataPool->getMetadata(CategoryInterface::class);
        $this->entityTable = $categoryMetaData->getEntityTable();
        $this->entityIdField = $categoryMetaData->getIdentifierField();
        $this->linkField = $categoryMetaData->getLinkField();
        $this->entityTypeId = (int) $eavConfig->getEntityType(Category::ENTITY)->getId();
    }

    public function getEntityTable(): string {
        return $this->entityTable;
    }

    public function getEntityIdField(): string {
        return $this->entityIdField;
    }

    public function getLinkField(): string {
        return $this->linkField;
    }

    public function getEntityTypeId(): int {
        return $this->entityTypeId;
    }
}

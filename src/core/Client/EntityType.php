<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use StreamX\ConnectorCatalog\Indexer\CategoryIndexer;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;

class EntityType {

    private const PRODUCT = 'product';
    private const CATEGORY = 'category';

    private string $rootType;
    private string $fullyQualifiedName;

    public function getRootType(): string {
        return $this->rootType;
    }

    public function getFullyQualifiedName(): string {
        return $this->fullyQualifiedName;
    }

    private function __construct(string $rootType, string $fullyQualifiedName) {
        $this->rootType = $rootType;
        $this->fullyQualifiedName = $fullyQualifiedName;
    }

    private static function productEntityType(string $productSubtype = ''): self {
        $fullyQualifiedName = self::PRODUCT;
        if (!empty($productSubtype)) {
            $fullyQualifiedName .= "/$productSubtype";
        }
        return new EntityType(self::PRODUCT, $fullyQualifiedName);
    }

    private static function categoryEntityType(): self {
        return new EntityType(self::CATEGORY, self::CATEGORY);
    }

    public static function fromEntityAndIndexerId(array $entity, string $indexerId): self {
        if ($indexerId == ProductIndexer::INDEXER_ID) {
            if (!empty($entity['variants'])) {
                return self::productEntityType('master');
            }

            return self::productEntityType('simple');
        }
        if ($indexerId == CategoryIndexer::INDEXER_ID) {
            return self::categoryEntityType();
        }
        throw new Exception("Received data from unexpected indexer: $indexerId");
    }

    public static function fromIndexerId(string $indexerId): self {
        if ($indexerId == ProductIndexer::INDEXER_ID) {
            return self::productEntityType();
        }
        if ($indexerId == CategoryIndexer::INDEXER_ID) {
            return self::categoryEntityType();
        }
        throw new Exception("Received data from unexpected indexer: $indexerId");
    }

}
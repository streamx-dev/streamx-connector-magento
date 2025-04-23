<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

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
        if ($indexerId == ProductProcessor::INDEXER_ID) {
            if (!empty($entity['variants'])) {
                return self::productEntityType('master');
            }

            return self::productEntityType('simple');
        }
        if ($indexerId == CategoryProcessor::INDEXER_ID) {
            return self::categoryEntityType();
        }
        throw new Exception("Received data from unexpected indexer: $indexerId");
    }

    public static function fromIndexerId(string $indexerId): self {
        if ($indexerId == ProductProcessor::INDEXER_ID) {
            return self::productEntityType();
        }
        if ($indexerId == CategoryProcessor::INDEXER_ID) {
            return self::categoryEntityType();
        }
        throw new Exception("Received data from unexpected indexer: $indexerId");
    }

}
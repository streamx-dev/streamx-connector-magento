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

    public static function fromEntityAndIndexerName(array $entity, string $indexerName): self {
        if ($indexerName == ProductProcessor::INDEXER_ID) {
            if (isset($entity['bundle_options'])) {
                // TODO: bundle products will be refactored, the above condition may become different
                return self::productEntityType('bundle');
            }
            // TODO: for grouped products, currently the produced json doesn't contain information about the components that make up the grouped product - so we can't currently detect a "grouped product"
            if (!empty($entity['variants'])) {
                return self::productEntityType('master');
            }

            return self::productEntityType('simple');
        }
        if ($indexerName == CategoryProcessor::INDEXER_ID) {
            return self::categoryEntityType();
        }
        throw new Exception("Received data from unexpected indexer: $indexerName");
    }

    public static function fromIndexerName(string $indexerName): self {
        if ($indexerName == ProductProcessor::INDEXER_ID) {
            return self::productEntityType();
        }
        if ($indexerName == CategoryProcessor::INDEXER_ID) {
            return self::categoryEntityType();
        }
        throw new Exception("Received data from unexpected indexer: $indexerName");
    }

}
MView processing is implemented in Magento in multi-layered way and that causes exceptions logs being not available.

During tests execution, you may receive a generic error such as:
`Error when updating an mview, trace: \var\www\html\vendor\magento\framework\Interception\Interceptor.php (...)`

To troubleshoot, temporarily edit (and upload to Magento) the `StreamxIndexerMviewProcessor` class.
The below instructions are for troubleshooting the products indexer.

1. Add uses:
```php
use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Model\Indexer\Attribute;
use StreamX\ConnectorCatalog\Model\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCatalog\Model\Indexer\Category;
use StreamX\ConnectorCatalog\Model\Indexer\Product;
```

2. Declare the below fields:
```php
    private ResourceConnection $resource;
    private array $indexers;
```

3. Add constructor parameters:
```php
    ResourceConnection $resource,
    Attribute $attrMviewAction,
    Category $catMviewAction,
    Product $prodMviewAction
```

4. Init fields inside constructor:
```php
        $this->resource = $resource;
        $this->indexers = [
            AttributeProcessor::INDEXER_ID => $attrMviewAction,
            CategoryProcessor::INDEXER_ID => $catMviewAction,
            ProductProcessor::INDEXER_ID => $prodMviewAction
        ];
```

5. In the function `reindexMview` replace `$mView->update();` with:
```php
    $this->updateViewManually($mView, $this->indexers[$indexerViewId]);
```

6. Add the functions to the class:
```php
    private function updateViewManually(ViewInterface $mView, BaseStreamxIndexer $indexer): void {
        $ids = $this->selectIdsToReindex($mView);
        $indexer->execute($ids);
        $mView->getState()->loadByView($mView->getId());
    }

    private function selectIdsToReindex(ViewInterface $mView): array {
        $changelog = $mView->getChangelog();
        $fromVersionId = (int)$mView->getState()->getVersionId();
        $toVersionId = $changelog->getVersion();

        $connection = $this->resource->getConnection();
        $changelogTableName = $this->resource->getTableName($changelog->getName());

        $select = $connection->select()->distinct()
            ->where("version_id > $fromVersionId")
            ->where("version_id <= $toVersionId")
            ->group([$changelog->getColumnName()])
            ->from($changelogTableName, [$changelog->getColumnName()]);

        return $connection->fetchCol($select);
    }
```
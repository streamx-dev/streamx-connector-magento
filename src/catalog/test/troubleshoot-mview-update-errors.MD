MView processing is implemented in Magento in multi-layered way and that causes exceptions logs being not available.

During tests execution, you may receive a generic error such as:
`Error when updating an mview, trace: \var\www\html\vendor\magento\framework\Interception\Interceptor.php (...)`

To troubleshoot, temporarily edit (and upload to Magento) the `StreamxIndexerMviewProcessor` class.
The below instructions are for troubleshooting the products indexer.

1. Add uses:
```php
use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Model\Indexer\Product;
```

2. Add declare the below fields and inject them via constructor:
```php
    private Product $productMviewAction;
    private ResourceConnection $resource;
```

3. In the function `reindexMview` replace `$mView->update();` with:
```php
        if ($indexerViewId === ProductProcessor::INDEXER_ID) {
            $this->updateViewManually($mView);
        } else {
            $mView->update();
        }
```

4. Add the functions to the class:
```php
    private function updateViewManually(ViewInterface $mView): void {
        $ids = $this->selectIdsToReindex($mView);
        $this->productMviewAction->execute($ids);
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
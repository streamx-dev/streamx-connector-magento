MView processing is implemented in Magento in multi-layered way and that causes exceptions logs being not available.

During tests execution, you may receive a generic error such as:
`Error when updating an mview, trace: \var\www\html\vendor\magento\framework\Interception\Interceptor.php (...)`

To troubleshoot, temporarily edit (and upload to Magento) the `StreamxIndexerMviewProcessor` class.
The below instructions are for troubleshooting the products indexer.

## 1. In the function `reindexMview` replace `$mView->update();` with:
```php
    $this->updateViewManually($mView, $indexerViewId);
```

## 2. Add the functions to the class:
```php
    private function updateViewManually(ViewInterface $mView, string $indexerViewId): void {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $indexers = [
            AttributeProcessor::INDEXER_ID => $objectManager->get(\StreamX\ConnectorCatalog\Model\Indexer\Attribute::class),
            CategoryProcessor::INDEXER_ID => $objectManager->get(\StreamX\ConnectorCatalog\Model\Indexer\Category::class),
            ProductProcessor::INDEXER_ID => $objectManager->get(\StreamX\ConnectorCatalog\Model\Indexer\Product::class)
        ];
        $indexer = $indexers[$indexerViewId];

        $ids = $this->selectIdsToReindex($mView);
        $indexer->execute($ids);
        $mView->getState()->loadByView($mView->getId());
    }

    private function selectIdsToReindex(ViewInterface $mView): array {
        $changelog = $mView->getChangelog();
        $fromVersionId = (int)$mView->getState()->getVersionId();
        $toVersionId = $changelog->getVersion();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $changelogTableName = $resource->getTableName($changelog->getName());

        $select = $connection->select()->distinct()
            ->where("version_id > $fromVersionId")
            ->where("version_id <= $toVersionId")
            ->group([$changelog->getColumnName()])
            ->from($changelogTableName, [$changelog->getColumnName()]);

        return $connection->fetchCol($select);
    }
```
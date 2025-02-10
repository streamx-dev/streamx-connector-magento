<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Index;

class BulkRequest {

    private array $bulkData = [];

    private function __construct() {
        // use builders instead
    }

    public static function buildUnpublishRequest(string $indexerName, array $entityIds): BulkRequest {
        $bulkRequest = new BulkRequest();
        foreach ($entityIds as $id) {
            $bulkRequest->bulkData[] = [
                'unpublish' => [
                    'indexer_name' => $indexerName,
                    'id' => $id,
                ]
            ];
        }

        return $bulkRequest;
    }

    public static function buildPublishRequest(string $indexerName, array $entities): BulkRequest {
        $bulkRequest = new BulkRequest();
        foreach ($entities as $entityData) {
            unset($entityData['entity_id']);
            unset($entityData['row_id']);

            $bulkRequest->bulkData[] = [
                'publish' => [
                    'indexer_name' => $indexerName,
                    'entity' => $entityData,
                ]
            ];
        }

        return $bulkRequest;
    }

    public function isEmpty(): bool {
        return count($this->bulkData) == 0;
    }

    /**
     * Return list of operations to be executed as an array.
     */
    public function getOperations(): array {
        return $this->bulkData;
    }
}

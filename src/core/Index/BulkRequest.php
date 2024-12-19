<?php

namespace StreamX\ConnectorCore\Index;

class BulkRequest {

    private array $bulkData = [];

    private function __construct() {
        // use builders instead
    }

    public static function buildUnpublishRequest(string $entityType, array $entityIds): BulkRequest {
        $bulkRequest = new BulkRequest();
        foreach ($entityIds as $id) {
            $bulkRequest->bulkData[] = [
                'delete' => [
                    'type' => $entityType,
                    'id' => $id,
                ]
            ];
        }

        return $bulkRequest;
    }

    public static function buildPublishRequest(string $entityType, array $entityIdAndContentMap): BulkRequest {
        $bulkRequest = new BulkRequest();
        foreach ($entityIdAndContentMap as $id => $entityData) {
            unset($entityData['entity_id']);
            unset($entityData['row_id']);

            // TODO: put all in one bulkData[] array item instead of two?
            $bulkRequest->bulkData[] = [
                'index' => [
                    '_type' => $entityType,
                    '_id' => $id,
                ]
            ];

            $bulkRequest->bulkData[] = $entityData;
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

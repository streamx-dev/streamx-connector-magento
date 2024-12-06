<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\BulkRequestInterface;

class BulkRequest implements BulkRequestInterface
{
    /**
     * Bulk operation stack.
     */
    private array $bulkData = [];

    public function deleteDocuments(string $type, array $docIds): BulkRequestInterface {
        foreach ($docIds as $docId) {
            $this->deleteDocument($type, $docId);
        }

        return $this;
    }

    private function deleteDocument(string $type, $docId): void {
        $this->bulkData[] = [
            'delete' => [
                '_type' => $type,
                '_id' => $docId,
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function addDocuments(string $type, array $data): BulkRequestInterface {
        foreach ($data as $docId => $documentData) {
            $documentData = $this->prepareDocument($documentData);
            $this->addDocument($type, $docId, $documentData);
        }

        return $this;
    }

    public function prepareDocument(array $data): array
    {
        unset($data['entity_id']);
        unset($data['row_id']);

        return $data;
    }

    private function addDocument($type, $docId, array $data): void {
        $this->bulkData[] = [
            'index' => [
                '_type' => $type,
                '_id' => $docId,
            ]
        ];

        $this->bulkData[] = $data;
    }

    /**
     * @inheritdoc
     */
    public function updateDocuments(string $type, array $data): BulkRequestInterface {
        foreach ($data as $docId => $documentData) {
            $documentData = $this->prepareDocument($documentData);
            $this->updateDocument($type, $docId, $documentData);
        }

        return $this;
    }

    private function updateDocument($type, $docId, array $data): void {
        $this->bulkData[] = [
            'update' => [
                '_id' => $docId,
                '_type' => $type,
            ]
        ];

        $this->bulkData[] = ['doc' => $data];
    }

    /**
     * @inheritdoc
     */
    public function isEmpty(): bool {
        return count($this->bulkData) == 0;
    }

    /**
     * @inheritdoc
     */
    public function getOperations(): array {
        return $this->bulkData;
    }
}

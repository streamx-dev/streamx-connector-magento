<?php

namespace StreamX\ConnectorCore\Index;

class BulkRequest
{
    /**
     * Bulk operation stack.
     */
    private array $bulkData = [];

    public function deleteDocuments(string $type, array $docIds): BulkRequest {
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
     * $data format have to be an array of all documents with document id as key.
     *
     * @param string $type  Document type.
     * @param array  $data  Document data.
     */
    public function addDocuments(string $type, array $data): BulkRequest {
        foreach ($data as $docId => $documentData) {
            $this->prepareDocument($documentData);
            $this->addDocument($type, $docId, $documentData);
        }

        return $this;
    }

    private function prepareDocument(array $data): void {
        unset($data['entity_id']);
        unset($data['row_id']);
    }

    private function addDocument($type, $docId, array $data): void {
        // TODO: put all in one bulkData[] array item instead of two?
        $this->bulkData[] = [
            'index' => [
                '_type' => $type,
                '_id' => $docId,
            ]
        ];

        $this->bulkData[] = $data;
    }

    /**
     * Indicates if the current bulk contains operation.
     */
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

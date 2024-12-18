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

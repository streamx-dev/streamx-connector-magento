<?php

namespace Divante\VsbridgeIndexerCore\Index;

use Divante\VsbridgeIndexerCore\Api\BulkRequestInterface;

class BulkRequest implements BulkRequestInterface
{
    /**
     * Bulk operation stack.
     *
     * @var array
     */
    private $bulkData = [];

    /**
     * @inheritdoc
     */
    public function deleteDocuments(string $index, string $type, array $docIds)
    {
        foreach ($docIds as $docId) {
            $this->deleteDocument($index, $type, $docId);
        }

        return $this;
    }

    /**
     * @param $docId
     *
     * @return $this
     */
    private function deleteDocument(string $index, string $type, $docId)
    {
        $this->bulkData[] = [
            'delete' => [
                '_index' => $index,
                '_type' => $type,
                '_id' => $docId,
            ]
        ];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addDocuments(string $index, string $type, array $data)
    {
        foreach ($data as $docId => $documentData) {
            $documentData = $this->prepareDocument($documentData);
            $this->addDocument($index, $type, $docId, $documentData);
        }

        return $this;
    }

    public function prepareDocument(array $data): array
    {
        unset($data['entity_id']);
        unset($data['row_id']);

        return $data;
    }

    /**
     * @inheritdoc
     */
    private function addDocument($index, $type, $docId, array $data)
    {
        $this->bulkData[] = [
            'index' => [
                '_index' => $index,
                '_type' => $type,
                '_id' => $docId,
            ]
        ];

        $this->bulkData[] = $data;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function updateDocuments(string $index, string $type, array $data)
    {
        foreach ($data as $docId => $documentData) {
            $documentData = $this->prepareDocument($documentData);
            $this->updateDocument($index, $type, $docId, $documentData);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    private function updateDocument($index, $type, $docId, array $data)
    {
        $this->bulkData[] = [
            'update' => [
                '_index' => $index,
                '_id' => $docId,
                '_type' => $type,
            ]
        ];

        $this->bulkData[] = ['doc' => $data];

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isEmpty(): bool
    {
        return count($this->bulkData) == 0;
    }

    /**
     * @inheritdoc
     */
    public function getOperations(): array
    {
        return $this->bulkData;
    }
}

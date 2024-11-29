<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface BulkRequestInterface
{
    /**
     * Indicates if the current bulk contains operation.
     */
    public function isEmpty(): bool;

    /**
     * Return list of operations to be executed as an array.
     */
    public function getOperations(): array;

    public function prepareDocument(array $data): array;

    /**
     * Add a several documents to the index.
     *
     * $data format have to be an array of all documents with document id as key.
     *
     * @param string $index Index the documents have to be added to.
     * @param string $type  Document type.
     * @param array  $data  Document data.
     *
     * @return \Divante\VsbridgeIndexerCore\Api\BulkRequestInterface
     */
    public function addDocuments(string $index, string $type, array $data);

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\BulkRequestInterface
     */
    public function deleteDocuments(string $index, string $type, array $docIds);

    /**
     * Update several documents to the index.
     *
     * $data format have to be an array of all documents with document id as key.
     *
     * @param string $index Index the documents have to be added to.
     * @param string $type  Document type.
     * @param array  $data  Document data.
     *
     * @return \Divante\VsbridgeIndexerCore\Api\BulkRequestInterface
     */
    public function updateDocuments(string $index, string $type, array $data);
}

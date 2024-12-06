<?php

namespace StreamX\ConnectorCore\Api;

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
     * $data format have to be an array of all documents with document id as key.
     *
     * @param string $type  Document type.
     * @param array  $data  Document data.
     */
    public function addDocuments(string $type, array $data): BulkRequestInterface;

    public function deleteDocuments(string $type, array $docIds): BulkRequestInterface;

    /**
     * $data format have to be an array of all documents with document id as key.
     *
     * @param string $type  Document type.
     * @param array  $data  Document data.
     */
    public function updateDocuments(string $type, array $data): BulkRequestInterface;
}

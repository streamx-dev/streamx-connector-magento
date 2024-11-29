<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\BulkResponseInterface;

class BulkResponse implements BulkResponseInterface
{
    /**
     * @var array
     */
    private $rawResponse;

    /**
     * @param array $rawResponse StreamX raw response.
     */
    public function __construct(array $rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }

    public function hasErrors(): bool
    {
        return (bool)$this->rawResponse['errors'];
    }

    public function getErrorItems(): array
    {
        return array_filter(
            $this->rawResponse['items'],
            function ($item) {
                return isset(current($item)['error']);
            }
        );
    }

    public function getSuccessItems(): array
    {
        $successes = array_filter(
            $this->rawResponse['items'],
            function ($item) {
                return !isset(current($item)['error']);
            }
        );

        return $successes;
    }

    public function aggregateErrorsByReason(): array
    {
        $errorByReason = [];

        foreach ($this->getErrorItems() as $item) {
            $operationType = current(array_keys($item));
            $itemData = $item[$operationType];
            $index = $itemData['_index'];
            $documentType = $itemData['_type'];
            $errorData = $itemData['error'];
            $errorKey = $operationType . $errorData['type'] . $errorData['reason'] . $index . $documentType;

            if (!isset($errorByReason[$errorKey])) {
                $errorByReason[$errorKey] = $this->prepareErrorByReason($item);
            }

            $errorByReason[$errorKey]['count'] += 1;
            $errorByReason[$errorKey]['document_ids'][] = $itemData['_id'];
        }

        return array_values($errorByReason);
    }

    private function prepareErrorByReason(array $item): array
    {
        $operationType = current(array_keys($item));
        $itemData = $item[$operationType];
        $errorData = $itemData['error'];

        return [
            'index' => $itemData['_index'],
            'document_type' => $itemData['_type'],
            'operation' => $operationType,
            'error' => [
                'type' => $errorData['type'],
                'reason' => $errorData['reason'],
            ],
            'count' => 0,
        ];
    }
}

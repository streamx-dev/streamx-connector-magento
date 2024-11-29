<?php

namespace Divante\VsbridgeIndexerCore\Index;

use Divante\VsbridgeIndexerCore\Api\BulkLoggerInterface;
use Divante\VsbridgeIndexerCore\Api\BulkResponseInterface;
use Psr\Log\LoggerInterface;

class BulkLogger implements BulkLoggerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

     public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(BulkResponseInterface $bulkResponse): void
    {
        if ($bulkResponse->hasErrors()) {
            $aggregateErrorsByReason = $bulkResponse->aggregateErrorsByReason();

            foreach ($aggregateErrorsByReason as $error) {
                $docIds = implode(', ', array_slice($error['document_ids'], 0, 10));
                $errorMessages = [
                    sprintf(
                        'Bulk %s operation failed %d times in index %s for type %s.',
                        $error['operation'],
                        $error['count'],
                        $error['index'],
                        $error['document_type']
                    ),
                    sprintf(
                        'Error (%s) : %s.',
                        $error['error']['type'],
                        $error['error']['reason']
                    ),
                    sprintf(
                        'Failed doc ids sample : %s.',
                        $docIds
                    ),
                ];

                $this->logger->error(implode(' ', $errorMessages));
            }
        }
    }
}

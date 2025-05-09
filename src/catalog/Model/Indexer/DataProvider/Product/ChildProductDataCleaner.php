<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

class ChildProductDataCleaner extends DataCleaner
{
    protected const FIELDS_TO_REMOVE = [
        'entity_id',
        'row_id',
        'type_id',
        'parent_id',
        'parent_ids'
    ];
}

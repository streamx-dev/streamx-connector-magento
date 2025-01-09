<?php

namespace StreamX\ConnectorCore\Api\Mapping;

interface FieldInterface
{
    const TYPE_KEYWORD = 'keyword';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DOUBLE = 'double';
    const TYPE_INTEGER = 'integer';
    const TYPE_LONG = 'long';
    const TYPE_TEXT = 'text';
    const TYPE_DATE = 'date';

    const DATE_FORMAT = 'yyyy-MM-dd HH:mm:ss||yyyy-MM-dd||epoch_millis';
}

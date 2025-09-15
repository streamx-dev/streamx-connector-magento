<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use InvalidArgumentException;

final class JsonFormatter {

    public static function formatJson(string $json): string {
        $parsedJson = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Error parsing json: ' . json_last_error_msg() . "\nThe JSON is: '$json'");
        }
        return json_encode($parsedJson, JSON_PRETTY_PRINT);
    }
}
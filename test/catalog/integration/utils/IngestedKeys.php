<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\test\integration\utils;

class IngestedKeys {

    private array $publishedKeys = []; // key = key, value = count
    private array $unpublishedKeys = []; // key = key, value = count

    public function addPublishedKeys(array $keys): void {
        self::addKeys($keys, $this->publishedKeys);
    }

    public function addUnpublishedKeys(array $keys): void {
        self::addKeys($keys, $this->unpublishedKeys);
    }

    private function addKeys(array $keys, array &$arrayToAddTo): void {
        foreach ($keys as $key) {
            $count = 1 + ($arrayToAddTo[$key] ?? 0);
            $arrayToAddTo[$key] = $count;
        }
    }

    public function formatted(): string {
        $resultLines = [];
        if (!empty($this->publishedKeys)) {
            array_push($resultLines, ...self::getFormattedKeys($this->publishedKeys, ' + '));
        }
        if (!empty($this->unpublishedKeys)) {
            array_push($resultLines, ...self::getFormattedKeys($this->unpublishedKeys, ' - '));
        }
        return implode("\n", $resultLines);
    }

    private static function getFormattedKeys(array $keys, string $linePrefix) : array {
        $prefixToIdWithCountMap = self::toPrefixToIdWithCountMap($keys);

        $resultLines = [];
        foreach ($prefixToIdWithCountMap as $keyPrefix => $ids) {
            sort($ids);
            $resultLine = "$linePrefix$keyPrefix: " . implode(', ', $ids);

            $doesAnyIdHaveCountOfTenOrMore = (bool) preg_grep('/x\s+\d{2,}/', $ids);
            if ($doesAnyIdHaveCountOfTenOrMore) {
                $resultLine = "\033[33m$resultLine\033[0m"; // when used with fwrite(STDOUT) - enables displayed lines in warning color
            }

            $resultLines[] = $resultLine;
        }
        return $resultLines;
    }

    private static function toPrefixToIdWithCountMap(array $keys): array {
        $prefixToIdWithCountMap = [];
        foreach ($keys as $key => $count) {
            $parts = explode(':', str_replace('"', '', $key));
            $prefix = $parts[0];
            $id = $parts[1];
            if ($count > 1) {
                $id .= " x $count";
            }
            $prefixToIdWithCountMap[$prefix][] = $id;
        }
        ksort($prefixToIdWithCountMap);
        return $prefixToIdWithCountMap;
    }

    public function empty(): bool {
        return empty($this->publishedKeys) && empty($this->unpublishedKeys);
    }

}
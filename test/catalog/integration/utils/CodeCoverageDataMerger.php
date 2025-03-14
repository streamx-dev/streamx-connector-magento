<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

final class CodeCoverageDataMerger {

    /**
     * @return array associative array: key = file path, value = array of covered lines info
     */
    public static function merge(array $coverageFilePaths): array {
        $summaryCoverageData = [];
        foreach ($coverageFilePaths as $coverageFilePath) {
            $coverageFileContent = file_get_contents($coverageFilePath);
            $coverageData = json_decode($coverageFileContent, true);
            foreach ($coverageData as $filePath => $coveredLineNumbers) {
                if (!array_key_exists($filePath, $summaryCoverageData)) {
                    $summaryCoverageData[$filePath] = [];
                }
                foreach ($coveredLineNumbers as $coveredLineNumber => $value) {
                    $summaryCoverageData[$filePath][$coveredLineNumber] = $value;
                }
            }
        }

        return $summaryCoverageData;
    }
}
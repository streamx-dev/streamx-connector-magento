<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use PHPUnit\Framework\ExpectationFailedException;

trait ValidationFileUtils  {

    public static function readValidationFileContent(string $validationFileName): string {
        $validationFilesDir = FileUtils::findFolder('resources/validation');
        return file_get_contents("$validationFilesDir/$validationFileName");
    }

    public function verifySameJsonsOrThrow(string $expectedFormattedJson, string $actualJson, array $regexReplacements = [], bool $ignoreOrderInArrays = false): void {
        $this->verifySameJsons($expectedFormattedJson, $actualJson, true, $regexReplacements, $ignoreOrderInArrays);
    }

    public function verifySameJsonsSilently(string $expectedFormattedJson, string $actualJson, array $regexReplacements = [], bool $ignoreOrderInArrays = false): bool {
        return $this->verifySameJsons($expectedFormattedJson, $actualJson, false, $regexReplacements, $ignoreOrderInArrays);
    }

    private function verifySameJsons(string $expectedFormattedJson, string $actualJson, bool $throwOnAssertionError, array $regexReplacements = [], bool $ignoreOrderInArrays = false): bool {
        $actualFormattedJson = JsonFormatter::formatJson($actualJson);
        try {
            $expected = self::standardizeNewlines(self::replaceRegexes($expectedFormattedJson, $regexReplacements));
            $actual = self::standardizeNewlines(self::replaceRegexes($actualFormattedJson, $regexReplacements));
            if ($ignoreOrderInArrays) {
                $this->compareJsonsIgnoringOrderInArrayFields($expected, $actual);
            } else {
                $this->assertEquals($expected, $actual);
            }
            return true;
        } catch (ExpectationFailedException $e) {
            if ($throwOnAssertionError) {
                throw $e;
            }
            return false;
        }
    }

    private function standardizeNewlines(string $json): string {
        return str_replace('\r\n', '\n', $json);
    }

    private function replaceRegexes(string $json, array $regexReplacements): string {
        foreach ($regexReplacements as $regex => $replacement) {
            $json = preg_replace('/' . $regex . '/', $replacement, $json);
        }
        return $json;
    }

    private function compareJsonsIgnoringOrderInArrayFields(string $expectedJson, string $actualJson): void {
        $expectedAsArray = json_decode($expectedJson, true);
        $actualAsArray = json_decode($actualJson, true);

        $this->sortArrayRecursive($expectedAsArray);
        $this->sortArrayRecursive($actualAsArray);

        $this->assertEquals($expectedAsArray, $actualAsArray);
    }

    private function sortArrayRecursive(&$array): void {
        if (!is_array($array)) {
            return;
        }

        sort($array);

        foreach ($array as &$value) {
            $this->sortArrayRecursive($value);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use PHPUnit\Framework\ExpectationFailedException;

trait ValidationFileUtils  {

    public static function readValidationFileContent(string $validationFileName): string {
        $validationFilesDir = FileUtils::findFolder('resources/validation');
        return file_get_contents("$validationFilesDir/$validationFileName");
    }

    public function verifySameJsonsOrThrow(string $expectedFormattedJson, string $actualJson, array $regexReplacements = []): void {
        $this->verifySameJsons($expectedFormattedJson, $actualJson, true, $regexReplacements);
    }

    public function verifySameJsonsSilently(string $expectedFormattedJson, string $actualJson, array $regexReplacements = []): bool {
        return $this->verifySameJsons($expectedFormattedJson, $actualJson, false, $regexReplacements);
    }

    private function verifySameJsons(string $expectedFormattedJson, string $actualJson, bool $throwOnAssertionError, array $regexReplacements = []): bool {
        $actualFormattedJson = JsonFormatter::formatJson($actualJson);
        try {
            $expected = self::standardizeNewlines($expectedFormattedJson);
            // allow adjusting actual json to match expected validation json
            $actual = self::standardizeNewlines(self::replaceRegexes($actualFormattedJson, $regexReplacements));
            $this->assertEquals($expected, $actual);
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
            $json = preg_replace("|$regex|m", $replacement, $json);
        }
        return $json;
    }
}